<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassOffering;
use App\Models\StudentAcademic;
use App\Models\StudentClassEnrollment;
use App\Models\StudentCurriculumSubject;
use App\Models\CustomStudentCurriculumSubject;
use App\Models\ClassOfferingFinalization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Notifications\StudentEvaluationResultNotification;

class EvaluationController extends Controller
{
    public function index()
    {
        $teacherId = Auth::id();

        $classes = ClassOffering::query()
            ->whereHas('meetingsAll', fn ($q) => $q->where('teacher_id', $teacherId))
            ->with([
                'section',
                'curriculumTermSubject.subject',
                'curriculumTermSubject.term',
                'meetingsAll' => fn ($q) => $q->where('teacher_id', $teacherId),
            ])
            ->orderByDesc('end_date')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();

        return view('teacher.evaluations.index', compact('classes'));
    }

    public function show(ClassOffering $classOffering)
    {
        $this->authorizeTeacherForClass($classOffering);

        $ctsId = $classOffering->curriculum_term_subject_id;
        $subjectId = optional($classOffering->curriculumTermSubject)->subject_id;

        // 1) Get students who are REGULAR in this section (via student_academics.section_id)
        $sectionId = $classOffering->section_id;

        $regularStudentIds = StudentAcademic::query()
            ->where('section_id', $sectionId)
            ->pluck('user_id')
            ->unique()
            ->values();

        // 2) Get students who are enrolled specifically in this offering (usually additional)
        $offeringEnrollments = StudentClassEnrollment::query()
            ->where('class_offering_id', $classOffering->id)
            ->whereIn('status', ['enrolled', 'completed'])
            ->with('student')
            ->get()
            ->keyBy('user_id');

        // 3) Ensure REGULAR students have an enrollment row too
        foreach ($regularStudentIds as $userId) {
            StudentClassEnrollment::updateOrCreate(
                [
                    'class_offering_id' => $classOffering->id,
                    'user_id' => $userId,
                ],
                [
                    'status' => $offeringEnrollments->has($userId)
                        ? $offeringEnrollments[$userId]->status
                        : 'enrolled',
                    'is_additional' => $offeringEnrollments->has($userId)
                        ? (int)($offeringEnrollments[$userId]->is_additional ?? 0)
                        : 0,
                ]
            );
        }

        // 4) Reload the enrollments (now includes regular + additional)
        $enrollments = StudentClassEnrollment::query()
            ->where('class_offering_id', $classOffering->id)
            ->whereIn('status', ['enrolled', 'completed'])
            ->with('student')
            ->get();

        // 5) Academics for everyone in roster
        $studentIds = $enrollments->pluck('user_id')->unique()->values();

        $academics = StudentAcademic::query()
            ->whereIn('user_id', $studentIds)
            ->get()
            ->keyBy('user_id');

        // 6) Status maps (official/custom)
        $officialStatuses = StudentCurriculumSubject::query()
            ->whereIn('student_academic_id', $academics->pluck('id'))
            ->where('curriculum_term_subject_id', $ctsId)
            ->get()
            ->keyBy('student_academic_id');

        $customStatuses = CustomStudentCurriculumSubject::query()
            ->whereIn('student_academic_id', $academics->pluck('id'))
            ->where('subject_id', $subjectId)
            ->get()
            ->keyBy('student_academic_id');

        // ✅ finalization info
        $classOffering->load('finalization.finalizedBy');
        $isFinalized = $classOffering->isFinalized();
        $finalization = $classOffering->finalization;

        return view('teacher.evaluations.show', compact(
            'classOffering',
            'enrollments',
            'academics',
            'officialStatuses',
            'customStatuses',
            'subjectId',
            'ctsId',
            'isFinalized',
            'finalization'
        ));
    }

    public function store(Request $request, ClassOffering $classOffering)
    {
        $classOffering->load(['finalization', 'curriculumTermSubject.subject', 'curriculumTermSubject.term', 'section']);

        if ($classOffering->isFinalized()) {
            abort(403, 'This class offering is finalized and cannot be edited.');
        }

        $this->authorizeTeacherForClass($classOffering);

        $data = $request->validate([
            'results' => ['required', 'array'],
            'results.*.status' => ['required', 'in:passed,failed'],
            'results.*.remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $teacherId = Auth::id();
        $now = now();

        $ctsId = $classOffering->curriculum_term_subject_id;
        $subject = $classOffering->curriculumTermSubject?->subject;
        $term = $classOffering->curriculumTermSubject?->term;
        $subjectId = $subject?->id;

        // We'll gather notifications here, then send after commit.
        $toNotify = [];

        DB::transaction(function () use ($data, $classOffering, $teacherId, $now, $ctsId, $subjectId, &$toNotify) {
            foreach ($data['results'] as $userId => $payload) {
                $status = $payload['status'];
                $remarks = $payload['remarks'] ?? null;

                $academic = StudentAcademic::where('user_id', $userId)->first();
                if (!$academic) continue;

                // Check if this student is enrolled in this class offering
                $enrollment = StudentClassEnrollment::where('class_offering_id', $classOffering->id)
                    ->where('user_id', $userId)
                    ->first();

                if (!$enrollment) continue;

                $isAdditional = ((int)$enrollment->is_additional === 1);

                if ($isAdditional) {
                    // CUSTOM / ADDITIONAL: match by (student_academic_id + subject_id)
                    CustomStudentCurriculumSubject::updateOrCreate(
                        [
                            'student_academic_id' => $academic->id,
                            'subject_id' => $subjectId,
                        ],
                        [
                            'status' => $status,
                            'remarks' => $remarks,
                            'evaluated_by' => $teacherId,
                            'evaluated_at' => $now,
                        ]
                    );
                } else {
                    // OFFICIAL: match by (student_academic_id + curriculum_term_subject_id)
                    StudentCurriculumSubject::where('student_academic_id', $academic->id)
                        ->where('curriculum_term_subject_id', $ctsId)
                        ->update([
                            'status' => $status,
                            'remarks' => $remarks,
                            'evaluated_by' => $teacherId,
                            'evaluated_at' => $now,
                            'class_offering_id' => $classOffering->id,
                        ]);
                }

                // Mark enrollment completed
                $enrollment->update(['status' => 'completed']);

                // Queue the notification meta
                $toNotify[] = [
                    'user_id' => (int)$userId,
                    'status' => $status,
                    'remarks' => $remarks,
                    'is_additional' => $isAdditional,
                ];
            }
        });

        // ✅ Notify after successful transaction
        foreach ($toNotify as $row) {
            try {
                $student = User::find($row['user_id']);
                if (!$student || empty($student->email)) continue;

                $student->notify(new StudentEvaluationResultNotification(
                    result: $row['status'],
                    meta: [
                        'subject_code' => $subject?->code,
                        'subject_name' => $subject?->name,
                        'term' => $term?->name,
                        'section' => $classOffering->section?->section_name ?? $classOffering->section?->name ?? null,
                        'remarks' => $row['remarks'],
                        'url' => url('/student/schedule'), // change if you have a specific evaluation page
                    ]
                ));
            } catch (\Throwable $e) {
                Log::error('Evaluation notification failed', [
                    'class_offering_id' => $classOffering->id,
                    'student_user_id' => $row['user_id'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return redirect()
            ->route('teacher.evaluations.show', $classOffering->id)
            ->with('success', 'Evaluations saved and students notified.');
    }

    private function authorizeTeacherForClass(ClassOffering $classOffering): void
    {
        $teacherId = Auth::id();

        $isHandled = $classOffering->meetingsAll()
            ->where('teacher_id', $teacherId)
            ->exists();

        abort_unless($isHandled, 403, 'You are not assigned to this class.');
    }

    public function finalize(ClassOffering $classOffering)
    {
        $this->authorizeTeacherForClass($classOffering);

        $classOffering->load('finalization');

        if ($classOffering->isFinalized()) {
            return back()->withErrors(['finalize' => 'This offering is already finalized.']);
        }

        $ctsId = $classOffering->curriculum_term_subject_id;
        $subjectId = optional($classOffering->curriculumTermSubject)->subject_id;

        // Use the same roster definition as your show()
        $enrollments = StudentClassEnrollment::query()
            ->where('class_offering_id', $classOffering->id)
            ->whereIn('status', ['enrolled', 'completed'])
            ->get();

        $studentIds = $enrollments->pluck('user_id')->unique()->values();

        $academics = StudentAcademic::query()
            ->whereIn('user_id', $studentIds)
            ->get()
            ->keyBy('user_id');

        $missing = [];

        foreach ($enrollments as $enrollment) {
            $userId = $enrollment->user_id;
            $academic = $academics[$userId] ?? null;
            if (!$academic) {
                $missing[] = "Student #$userId (no academic record)";
                continue;
            }

            $isAdditional = (int)$enrollment->is_additional === 1;

            if ($isAdditional) {
                $row = CustomStudentCurriculumSubject::query()
                    ->where('student_academic_id', $academic->id)
                    ->where('subject_id', $subjectId)
                    ->first();

                if (!$row || !in_array($row->status, ['passed', 'failed'], true)) {
                    $missing[] = "Student #$userId (additional)";
                }
            } else {
                $row = StudentCurriculumSubject::query()
                    ->where('student_academic_id', $academic->id)
                    ->where('curriculum_term_subject_id', $ctsId)
                    ->first();

                if (!$row || !in_array($row->status, ['passed', 'failed'], true)) {
                    $missing[] = "Student #$userId (official)";
                }
            }
        }

        if (!empty($missing)) {
            return back()->withErrors([
                'finalize' => 'Cannot finalize. Some students have no result yet: ' . implode(', ', $missing),
            ]);
        }

        DB::transaction(function () use ($classOffering) {
            ClassOfferingFinalization::updateOrCreate(
                ['class_offering_id' => $classOffering->id],
                [
                    'finalized_at' => now(),
                    'finalized_by' => Auth::id(),
                    'unlocked_at' => null,
                    'unlocked_by' => null,
                    'unlock_reason' => null,
                ]
            );
        });

        return redirect()
            ->route('teacher.evaluations.show', $classOffering->id)
            ->with('success', 'Class offering finalized. Evaluations are now locked.');
    }
}
