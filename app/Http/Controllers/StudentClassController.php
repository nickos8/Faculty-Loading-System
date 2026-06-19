<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ClassOffering;
use App\Models\StudentAcademic;
use App\Models\StudentClassEnrollment;
use App\Models\StudentCurriculumSubject;
use App\Models\CustomStudentCurriculumSubject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Notifications\StudentSubjectStatusNotification;
use Illuminate\Support\Facades\Log;

class StudentClassController extends Controller
{
    public function index(Request $request, User $user)
    {
        $student = $user;

        $studentAcademic = StudentAcademic::with(['program', 'curriculum', 'section'])
            ->where('user_id', $student->id)
            ->firstOrFail();

        $section = $studentAcademic->section;

        // ✅ status map subject_id => status (official via join + custom + enrolled overlay)
        $statusBySubjectId = $this->buildStudentSubjectStatusMap($studentAcademic->id, $student->id);

        $dayNames = [
            1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday',
            5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday',
        ];

        // Section offerings (for the section table)
        $sectionOfferings = collect();

        if ($studentAcademic->section_id) {
            $ids = DB::table('class_offerings')
                ->where('section_id', $studentAcademic->section_id)
                ->orderByDesc('id')
                ->pluck('id');

            $sectionOfferings = ClassOffering::with([
                    'section',
                    'meetings.teacher',
                    'meetings.room',
                    'curriculumTermSubject.subject.prerequisites',
                ])
                ->whereIn('id', $ids)
                ->get();
        }

        // ✅ ENROLLED ONLY (for current schedule)
        $enrollments = StudentClassEnrollment::with([
                'classOffering.section',
                'classOffering.curriculumTermSubject.subject',
                'classOffering.meetings.teacher',
                'classOffering.meetings.room',
            ])
            ->where('user_id', $user->id)
            ->where('status', 'enrolled')
            ->orderByDesc('id')
            ->get();

        // IMPORTANT: this should be based on enrolled only
        $enrolledOfferingIds = $enrollments->pluck('class_offering_id')->all();

        // Search offerings
        $search = trim((string)$request->get('q', ''));
        $availableOfferings = collect();

        if ($search !== '') {
            $availableOfferings = ClassOffering::query()
                ->whereHas('curriculumTermSubject.subject', function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%");
                })
                ->with([
                    'section',
                    'meetings.teacher',
                    'meetings.room',
                    'curriculumTermSubject.subject.prerequisites',
                ])
                ->orderByDesc('id')
                ->limit(200)
                ->get();
        }

        return view('program-admin.students.classes.index', compact(
            'student',
            'studentAcademic',
            'section',
            'sectionOfferings',
            'enrollments',
            'enrolledOfferingIds',
            'availableOfferings',
            'search',
            'dayNames',
            'statusBySubjectId'
        ));
    }

    public function history(Request $request, User $user)
    {
        $admin = $request->user();
        $studentAcademic = $user->studentAcademic;

        if (! $studentAcademic || (int)$studentAcademic->program_id !== (int)$admin->program_id) {
            abort(403, 'You are not allowed to view this student.');
        }

        $history = StudentClassEnrollment::query()
            ->where('user_id', $user->id)
            ->with([
                'classOffering.section',
                'classOffering.curriculumTermSubject.subject',
                'classOffering.meetings.teacher',
                'classOffering.meetings.room',
            ])
            ->orderByDesc('created_at')
            ->paginate(15);

        $dayNames = [
            1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday',
            5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday',
        ];

        return view('student.schedule.history', [
            'student'  => $user,
            'history'  => $history,
            'dayNames' => $dayNames,
        ]);
    }

    public function store(Request $request, User $user)
    {
        $admin = $request->user();
        $student = $user;

        $studentAcademic = StudentAcademic::where('user_id', $student->id)->first();

        if (! $studentAcademic || (int)$studentAcademic->program_id !== (int)$admin->program_id) {
            abort(403, 'You are not allowed to manage this student.');
        }

        $data = $request->validate([
            'class_offering_id' => ['required', 'exists:class_offerings,id'],
            'is_additional'     => ['nullable', 'boolean'],
        ]);

        $offering = ClassOffering::with([
                'meetings',
                'curriculumTermSubject.subject.prerequisites',
                'curriculumTermSubject.term',
            ])
            ->findOrFail($data['class_offering_id']);

        $term = $offering->curriculumTermSubject?->term; // ✅ used in notification meta

        $subject = $offering->curriculumTermSubject?->subject;
        $newSubjectId = $subject?->id;

        if (! $newSubjectId) {
            throw ValidationException::withMessages([
                'class_offering_id' =>
                    "Unable to add this offering.\n"
                    . "Reason: This offering is not linked to a valid subject.\n"
                    . "Action: Edit the offering and assign a curriculum term subject first.",
            ]);
        }

        $subjectLabel = trim(($subject->code ?? 'UNKNOWN') . ' — ' . ($subject->name ?? 'Unknown Subject'));

        /**
         * ✅ RULE A: Must be in student's curriculum OR in student's custom list
         * FIXED: curriculum_term_subjects has no curriculum_id
         * We must join curriculum_terms to filter by curriculum_id.
         */
        $isInCurriculum = DB::table('curriculum_term_subjects as cts')
            ->join('curriculum_terms as ct', 'ct.id', '=', 'cts.curriculum_term_id')
            ->where('cts.subject_id', $newSubjectId)
            ->where('ct.curriculum_id', $studentAcademic->curriculum_id)
            ->exists();

        $isInCustom = CustomStudentCurriculumSubject::query()
            ->where('student_academic_id', $studentAcademic->id)
            ->where('subject_id', $newSubjectId)
            ->exists();

        if (! $isInCurriculum && ! $isInCustom) {
            throw ValidationException::withMessages([
                'class_offering_id' =>
                    "Unable to add subject: {$subjectLabel}\n"
                    . "Reason: This subject is not part of the student's curriculum and is not listed in the student's custom subjects.\n"
                    . "Action: Add the subject to the student's curriculum/custom list first, then try again.",
            ]);
        }

        /**
         * ✅ RULE B: Block if already enrolled / passed / credited
         */
        $currentStatus = $this->getStudentSubjectStatus($studentAcademic->id, $student->id, $newSubjectId);

        if (in_array($currentStatus, ['passed', 'credited', 'enrolled'], true)) {
            throw ValidationException::withMessages([
                'class_offering_id' =>
                    "Unable to add subject: {$subjectLabel}\n"
                    . "Reason: Current status is '{$currentStatus}'.\n"
                    . "Action: A subject that is already ENROLLED, PASSED, or CREDITED cannot be scheduled again.",
            ]);
        }

        /**
         * ✅ RULE C: Prerequisites must be PASSED or CREDITED
         */
        $prereqs = $subject->prerequisites ?? collect();
        if ($prereqs->isNotEmpty()) {
            $missingDetails = [];

            foreach ($prereqs as $pre) {
                $preStatus = $this->getStudentSubjectStatus($studentAcademic->id, $student->id, (int)$pre->id);

                if (! in_array($preStatus, ['passed', 'credited'], true)) {
                    $missingDetails[] = "- {$pre->code} — {$pre->name} (Current status: {$preStatus})";
                }
            }

            if (!empty($missingDetails)) {
                throw ValidationException::withMessages([
                    'class_offering_id' =>
                        "Unable to add subject: {$subjectLabel}\n"
                        . "Reason: Missing prerequisite completion.\n"
                        . "Prerequisites not satisfied (must be PASSED or CREDITED):\n"
                        . implode("\n", $missingDetails) . "\n"
                        . "Action: Complete the prerequisite subject(s) first, then try again.",
                ]);
            }
        }

        // ✅ Do DB changes first, then notify (your notification has afterCommit=true anyway)
        DB::transaction(function () use ($student, $offering, $studentAcademic, $newSubjectId, $data) {

            // Schedule conflict check
            $newMeetings = $offering->meetings;

            $existingEnrollments = StudentClassEnrollment::query()
                ->where('user_id', $student->id)
                ->where('status', 'enrolled')
                ->with(['classOffering.meetings', 'classOffering.curriculumTermSubject.subject'])
                ->get();

            foreach ($existingEnrollments as $enr) {
                $existingOffering = $enr->classOffering;
                if (! $existingOffering) continue;

                foreach ($existingOffering->meetings as $ex) {
                    foreach ($newMeetings as $nm) {
                        if ((int)$ex->day_of_week !== (int)$nm->day_of_week) continue;

                        $overlap = ($nm->time_start < $ex->time_end) && ($ex->time_start < $nm->time_end);

                        if ($overlap) {
                            $existingSubject = $existingOffering->curriculumTermSubject?->subject;
                            $conflictSubject = $existingSubject
                                ? ($existingSubject->code . ' — ' . $existingSubject->name)
                                : 'another class';

                            $dayName = [
                                1=>'Monday',2=>'Tuesday',3=>'Wednesday',4=>'Thursday',
                                5=>'Friday',6=>'Saturday',7=>'Sunday'
                            ][$nm->day_of_week] ?? ('Day ' . $nm->day_of_week);

                            throw ValidationException::withMessages([
                                'class_offering_id' =>
                                    "Unable to add this class due to schedule conflict.\n"
                                    . "Conflict with: {$conflictSubject}\n"
                                    . "Day: {$dayName}\n"
                                    . "Existing: " . substr($ex->time_start, 0, 5) . "–" . substr($ex->time_end, 0, 5) . "\n"
                                    . "New: " . substr($nm->time_start, 0, 5) . "–" . substr($nm->time_end, 0, 5) . "\n"
                                    . "Action: Choose another offering/time slot that does not overlap.",
                            ]);
                        }
                    }
                }
            }

            // Create/update enrollment
            StudentClassEnrollment::updateOrCreate(
                [
                    'user_id'           => $student->id,
                    'class_offering_id' => $offering->id,
                ],
                [
                    'status'        => 'enrolled',
                    'is_additional' => (bool)($data['is_additional'] ?? true),
                ]
            );

            // ✅ Mark subject status as enrolled (official if exists else custom)
            $this->markSubjectStatusForStudentAcademic(
                $studentAcademic->id,
                $newSubjectId,
                'enrolled'
            );
        });

        // ✅ Notification: ENROLLED
        try {
            $subject = $offering->curriculumTermSubject?->subject;
            $term    = $offering->curriculumTermSubject?->term;

            $student->notify(new StudentSubjectStatusNotification(
                action: 'enrolled',
                meta: [
                    'subject_code' => $subject?->code,
                    'subject_name' => $subject?->name,
                    'term'         => $term?->name,
                    'school_year'  => $studentAcademic?->school_year,
                ]
            ));
        } catch (\Throwable $e) {
            Log::error('Enrollment notification failed', [
                'student_id' => $student->id,
                'offering_id' => $offering->id,
                'subject_id' => $newSubjectId,
                'error'      => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('program-admin.students.classes.index', $student->id)
            ->with('success', 'Class added to student.');
    }

    public function destroy(Request $request, User $user, StudentClassEnrollment $enrollment)
    {
        $admin = $request->user();
        $student = $user;

        $studentAcademic = StudentAcademic::where('user_id', $student->id)->first();

        if (! $studentAcademic || (int)$studentAcademic->program_id !== (int)$admin->program_id) {
            abort(403, 'You are not allowed to manage this student.');
        }

        if ((int)$enrollment->user_id !== (int)$student->id) {
            abort(403, 'Enrollment does not belong to this student.');
        }

        // We'll capture meta for notification outside transaction
        $offering = $enrollment->classOffering()->with([
            'curriculumTermSubject.subject',
            'curriculumTermSubject.term',
        ])->first();

        $subject = $offering?->curriculumTermSubject?->subject;
        $term    = $offering?->curriculumTermSubject?->term;

        DB::transaction(function () use ($student, $enrollment, $studentAcademic) {

            $subjectId = (int)($enrollment->classOffering?->curriculumTermSubject?->subject_id);

            $enrollment->update(['status' => 'dropped']);

            if (! $subjectId) return;

            $stillEnrolledSameSubject = StudentClassEnrollment::query()
                ->where('user_id', $student->id)
                ->where('status', 'enrolled')
                ->whereHas('classOffering.curriculumTermSubject', function ($q) use ($subjectId) {
                    $q->where('subject_id', $subjectId);
                })
                ->exists();

            if (! $stillEnrolledSameSubject) {
                $this->markSubjectStatusForStudentAcademic(
                    $studentAcademic->id,
                    $subjectId,
                    'failed'
                );
            }
        });

        // ✅ Notification: DROPPED
        try {
            $student->notify(new StudentSubjectStatusNotification(
                action: 'dropped',
                meta: [
                    'subject_code' => $subject?->code,
                    'subject_name' => $subject?->name,
                    'term'         => $term?->name,
                    'school_year'  => $studentAcademic?->school_year,
                ]
            ));
        } catch (\Throwable $e) {
            Log::error('Drop notification failed', [
                'student_id' => $student->id,
                'enrollment_id' => $enrollment->id,
                'offering_id' => $enrollment->class_offering_id,
                'error'      => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('program-admin.students.classes.index', $student->id)
            ->with('success', 'Class removed from student.');
    }

    /**
     * ✅ Correct: student_curriculum_subjects has no subject_id
     * We update official via curriculum_term_subjects join.
     */
    private function markSubjectStatusForStudentAcademic(int $studentAcademicId, int $subjectId, string $status): void
    {
        // Find an official scs row for this subject (via join)
        $officialRow = DB::table('student_curriculum_subjects as scs')
            ->join('curriculum_term_subjects as cts', 'cts.id', '=', 'scs.curriculum_term_subject_id')
            ->where('scs.student_academic_id', $studentAcademicId)
            ->where('cts.subject_id', $subjectId)
            ->select('scs.id')
            ->first();

        if ($officialRow) {
            StudentCurriculumSubject::where('id', $officialRow->id)
                ->update(['status' => $status]);

            return;
        }

        // Otherwise update custom
        CustomStudentCurriculumSubject::updateOrCreate(
            [
                'student_academic_id' => $studentAcademicId,
                'subject_id'          => $subjectId,
            ],
            [
                'status' => $status,
            ]
        );
    }

    /**
     * ✅ Correct official lookup via join (no subject_id in student_curriculum_subjects)
     */
    private function getStudentSubjectStatus(int $studentAcademicId, int $userId, int $subjectId): string
    {
        $isCurrentlyEnrolled = StudentClassEnrollment::query()
            ->where('user_id', $userId)
            ->where('status', 'enrolled')
            ->whereHas('classOffering.curriculumTermSubject', function ($q) use ($subjectId) {
                $q->where('subject_id', $subjectId);
            })
            ->exists();

        if ($isCurrentlyEnrolled) return 'enrolled';

        $official = DB::table('student_curriculum_subjects as scs')
            ->join('curriculum_term_subjects as cts', 'cts.id', '=', 'scs.curriculum_term_subject_id')
            ->where('scs.student_academic_id', $studentAcademicId)
            ->where('cts.subject_id', $subjectId)
            ->value('scs.status');

        if ($official) return (string)$official;

        $custom = CustomStudentCurriculumSubject::query()
            ->where('student_academic_id', $studentAcademicId)
            ->where('subject_id', $subjectId)
            ->value('status');

        if ($custom) return (string)$custom;

        return 'not_taken';
    }

    /**
     * ✅ Correct map builder via join
     */
    private function buildStudentSubjectStatusMap(int $studentAcademicId, int $userId): array
    {
        $map = [];

        $officialRows = DB::table('student_curriculum_subjects as scs')
            ->join('curriculum_term_subjects as cts', 'cts.id', '=', 'scs.curriculum_term_subject_id')
            ->where('scs.student_academic_id', $studentAcademicId)
            ->select(['cts.subject_id as subject_id', 'scs.status as status'])
            ->get();

        foreach ($officialRows as $r) {
            $map[(int)$r->subject_id] = (string)$r->status;
        }

        $customRows = DB::table('custom_student_curriculum_subjects')
            ->where('student_academic_id', $studentAcademicId)
            ->select(['subject_id', 'status'])
            ->get();

        foreach ($customRows as $r) {
            $sid = (int)$r->subject_id;
            if (!isset($map[$sid])) {
                $map[$sid] = (string)$r->status;
            }
        }

        $enrolledSubjectIds = DB::table('student_class_enrollments as sce')
            ->join('class_offerings as co', 'co.id', '=', 'sce.class_offering_id')
            ->join('curriculum_term_subjects as cts', 'cts.id', '=', 'co.curriculum_term_subject_id')
            ->where('sce.user_id', $userId)
            ->where('sce.status', 'enrolled')
            ->pluck('cts.subject_id')
            ->map(fn ($id) => (int)$id)
            ->unique()
            ->values()
            ->all();

        foreach ($enrolledSubjectIds as $sid) {
            $map[$sid] = 'enrolled';
        }

        return $map;
    }
}
