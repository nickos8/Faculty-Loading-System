<?php

namespace App\Http\Controllers\ProgramAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\StudentAcademic;
use App\Models\StudentCurriculumSubject;
use App\Models\CustomStudentCurriculumSubject;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StudentCurriculumManagementController extends Controller
{
    /**
     * Helper: build a comparable ordering number for a term.
     * If curriculum_terms.sequence exists, use it.
     * Otherwise, fallback to (year_level * 100 + term_no).
     * Unassigned => 9999.
     */
    private function termOrderFor(?int $curriculumId, ?int $yearLevel, ?int $termNo): int
    {
        if (!$yearLevel || !$termNo) {
            return 9999;
        }

        if ($curriculumId) {
            $seq = DB::table('curriculum_terms')
                ->where('curriculum_id', $curriculumId)
                ->where('year_level', $yearLevel)
                ->where('term_no', $termNo)
                ->value('sequence');

            if ($seq !== null) {
                return (int) $seq;
            }
        }

        return ($yearLevel * 100) + $termNo;
    }

    /**
     * Helper: map subject_id => earliest order found in this student's plan (OFFICIAL + CUSTOM).
     */

    public function searchSubjects(Request $request, User $student)
{
    $academic = StudentAcademic::where('user_id', $student->id)->firstOrFail();

    $q = trim((string) $request->query('q', ''));
    $limit = (int) $request->query('limit', 20);
    $limit = max(1, min($limit, 50));

    // If the frontend asks to resolve a specific subject id (for prefilling label)
    $id = $request->query('id');
    if ($id) {
        $s = Subject::select('id', 'code', 'name')
            ->where('status', 'active')
            ->find($id);

        return response()->json([
            'data' => $s ? [[
                'id' => $s->id,
                'code' => $s->code,
                'name' => $s->name,
                'label' => "{$s->code} — {$s->name}",
            ]] : [],
        ]);
    }

    // Exclude subjects already in this student's curriculum (official + custom)
    $officialIds = DB::table('student_curriculum_subjects as scs')
        ->join('curriculum_term_subjects as cts', 'cts.id', '=', 'scs.curriculum_term_subject_id')
        ->where('scs.student_academic_id', $academic->id)
        ->pluck('cts.subject_id');

    $customIds = DB::table('custom_student_curriculum_subjects')
        ->where('student_academic_id', $academic->id)
        ->whereNotNull('subject_id')
        ->pluck('subject_id');

    $excludeIds = $officialIds->merge($customIds)->unique()->values();

    $query = Subject::query()
        ->select('id', 'code', 'name')
        ->where('status', 'active');

    if ($q !== '') {
        // Uses your Subject::scopeSearch
        $query->where(function ($qq) use ($q) {
            $qq->where('code', 'like', '%' . $q . '%')
               ->orWhere('name', 'like', '%' . $q . '%');
        });
    } else {
        // If empty query, don’t dump random subjects
        return response()->json(['data' => []]);
    }

    if ($excludeIds->isNotEmpty()) {
        $query->whereNotIn('id', $excludeIds);
    }

    $results = $query
        ->orderBy('code')
        ->limit($limit)
        ->get()
        ->map(fn ($s) => [
            'id' => $s->id,
            'code' => $s->code,
            'name' => $s->name,
            'label' => "{$s->code} — {$s->name}",
        ])
        ->values();

    return response()->json(['data' => $results]);
}

    private function studentSubjectOrderMap(StudentAcademic $academic): array
    {
        $map = [];

        // OFFICIAL placement
        $official = DB::table('student_curriculum_subjects as scs')
            ->join('curriculum_term_subjects as cts', 'cts.id', '=', 'scs.curriculum_term_subject_id')
            ->join('curriculum_terms as ct', 'ct.id', '=', 'cts.curriculum_term_id')
            ->where('scs.student_academic_id', $academic->id)
            ->select('cts.subject_id', 'ct.curriculum_id', 'ct.year_level', 'ct.term_no', 'ct.sequence')
            ->get();

        foreach ($official as $r) {
            $sid = (int) $r->subject_id;

            $order = $r->sequence !== null
                ? (int) $r->sequence
                : $this->termOrderFor((int) $r->curriculum_id, (int) $r->year_level, (int) $r->term_no);

            if (!isset($map[$sid]) || $order < $map[$sid]) {
                $map[$sid] = $order;
            }
        }

        // CUSTOM placement
        $custom = DB::table('custom_student_curriculum_subjects')
            ->where('student_academic_id', $academic->id)
            ->whereNotNull('subject_id')
            ->select('subject_id', 'display_year_level', 'display_term_no')
            ->get();

        foreach ($custom as $r) {
            $sid = (int) $r->subject_id;

            $order = $this->termOrderFor(
                (int) $academic->curriculum_id,
                $r->display_year_level ? (int) $r->display_year_level : null,
                $r->display_term_no ? (int) $r->display_term_no : null
            );

            if (!isset($map[$sid]) || $order < $map[$sid]) {
                $map[$sid] = $order;
            }
        }

        return $map;
    }

    public function edit(User $student, Request $request)
    {
        $academic = StudentAcademic::with([
                'program',
                'curriculum',
                'section',
                'curriculumSubjects.curriculumTermSubject.subject',
                'curriculumSubjects.curriculumTermSubject.term',
            ])
            ->where('user_id', $student->id)
            ->firstOrFail();

        // OFFICIAL rows
        $officialRows = $academic->curriculumSubjects;

        // CUSTOM rows
        $customRows = $academic->customCurriculumSubjects()
            ->with('subject')
            ->orderByRaw('COALESCE(display_year_level, 99)')
            ->orderByRaw('COALESCE(display_term_no, 99)')
            ->orderBy('id')
            ->get();

        // Unified rows for single grouped display
        $allRows = collect()
            ->concat($officialRows->map(function ($row) {
                $term = $row->curriculumTermSubject?->term;

                return (object) [
                    'row_type'   => 'official',
                    'model'      => $row,
                    'year_level' => $term?->year_level,
                    'term_no'    => $term?->term_no,
                    'sort_key'   => $term ? ($term->year_level * 100 + $term->term_no) : 9999,
                ];
            }))
            ->concat($customRows->map(function ($row) {
                $y = $row->display_year_level;
                $t = $row->display_term_no;

                $hasTerm = $y && $t;

                return (object) [
                    'row_type'   => 'custom',
                    'model'      => $row,
                    'year_level' => $hasTerm ? $y : null,
                    'term_no'    => $hasTerm ? $t : null,
                    'sort_key'   => $hasTerm ? ($y * 100 + $t) : 9999,
                ];
            }));

        $groupedUnified = $allRows
            ->sortBy(fn ($x) => $x->sort_key)
            ->groupBy(function ($x) {
                if ($x->year_level && $x->term_no) {
                    return "Year {$x->year_level} - Term {$x->term_no}";
                }
                return 'Unassigned / Extra Subjects';
            });

       $subjects = Subject::where('status', 'active')
    ->orderBy('code')
    ->get();


        return view('program-admin.students.curriculum', [
            'student'        => $student,
            'academic'       => $academic,
            'groupedUnified' => $groupedUnified,
            'subjects'       => $subjects,
        ]);
    }

    /**
     * Update OFFICIAL + CUSTOM rows in one submit (arrays).
     */
    public function update(Request $request, User $student)
    {
        $academic = StudentAcademic::where('user_id', $student->id)->firstOrFail();

        $data = $request->validate([
            // OFFICIAL
            'status'        => ['array'],
            'status.*'      => ['in:not_taken,enrolled,passed,failed,credited'],
            'remarks'       => ['array'],
            'remarks.*'     => ['nullable', 'string', 'max:65535'],

            // CUSTOM
            'custom_status'    => ['array'],
            'custom_status.*'  => ['in:not_taken,enrolled,passed,failed,credited'],
            'custom_remarks'   => ['array'],
            'custom_remarks.*' => ['nullable', 'string', 'max:65535'],
        ]);

        $statusInput        = $data['status'] ?? [];
        $remarksInput       = $data['remarks'] ?? [];
        $customStatusInput  = $data['custom_status'] ?? [];
        $customRemarksInput = $data['custom_remarks'] ?? [];

        DB::transaction(function () use (
            $academic,
            $statusInput,
            $remarksInput,
            $customStatusInput,
            $customRemarksInput,
            $request
        ) {
            $now         = now();
            $evaluatorId = $request->user()->id;

            // Update OFFICIAL rows
            $scsList = StudentCurriculumSubject::where('student_academic_id', $academic->id)->get();

            foreach ($scsList as $row) {
                $id = $row->id;

                $newStatus  = $statusInput[$id] ?? $row->status;
                $newRemarks = $remarksInput[$id] ?? $row->remarks;

                if ($newStatus !== $row->status || $newRemarks !== $row->remarks) {
                    $row->status       = $newStatus;
                    $row->remarks      = $newRemarks;
                    $row->evaluated_by = $evaluatorId;
                    $row->evaluated_at = $now;
                    $row->save();
                }
            }

            // Update CUSTOM rows
            $customList = CustomStudentCurriculumSubject::where('student_academic_id', $academic->id)->get();

            foreach ($customList as $custom) {
                $id = $custom->id;

                $newStatus  = $customStatusInput[$id] ?? $custom->status;
                $newRemarks = $customRemarksInput[$id] ?? $custom->remarks;

                if ($newStatus !== $custom->status || $newRemarks !== $custom->remarks) {
                    $custom->status       = $newStatus;
                    $custom->remarks      = $newRemarks;
                    $custom->evaluated_by = $evaluatorId;
                    $custom->evaluated_at = $now;
                    $custom->save();
                }
            }
        });

        return back()->with('success', 'Student curriculum updated successfully.');
    }

    /**
     * ADD CUSTOM subject
     * - BLOCK duplicates (official + custom)
     * - THEN validate prerequisite placement
     */
    public function storeCustom(Request $request, User $student)
    {
        $academic = StudentAcademic::where('user_id', $student->id)->firstOrFail();

        // NOTE: kept your original "nullable subject_id" behavior? You requested the new validation,
        // which requires subject_id. Using the new one (required) because duplicates/prereqs need it.
        $data = $request->validate([
            'subject_id'            => ['required', 'exists:subjects,id'],
            'display_year_level'    => ['nullable', 'integer', 'min:1', 'max:10'],
            'display_term_no'       => ['nullable', 'integer', 'min:1', 'max:10'],
            'status'                => ['required', 'in:not_taken,enrolled,passed,failed,credited'],
            'remarks'               => ['nullable', 'string', 'max:1000'],
            'external_units'        => ['required', 'numeric', 'min:0.5', 'max:30'],
            'subject_type'          => ['nullable', 'in:major,minor,elective,general,thesis,internship'],
        ]);

        $data['external_school']       = null;
        $data['external_subject_code'] = null;
        $data['external_subject_name'] = null;
        $data['source_type'] = 'manual';

        $subjectId = (int) $data['subject_id'];

        // ✅ BLOCK DUPLICATE SUBJECTS (OFFICIAL + CUSTOM)
        $existsOfficial = DB::table('student_curriculum_subjects as scs')
            ->join('curriculum_term_subjects as cts', 'cts.id', '=', 'scs.curriculum_term_subject_id')
            ->where('scs.student_academic_id', $academic->id)
            ->where('cts.subject_id', $subjectId)
            ->exists();

        $existsCustom = DB::table('custom_student_curriculum_subjects')
            ->where('student_academic_id', $academic->id)
            ->where('subject_id', $subjectId)
            ->exists();

        if ($existsOfficial || $existsCustom) {
            $subject = Subject::find($subjectId);

            return back()->withErrors([
                'subject_id' =>
                    "Unable to add subject.\n\n" .
                    "Selected Subject: {$subject->code} — {$subject->name}\n\n" .
                    "Reason: This subject already exists in the student's curriculum.\n\n" .
                    "Action Required:\n" .
                    "• Edit the existing subject instead of adding a duplicate.\n" .
                    "• Or remove the existing subject first if re-adding is required."
            ])->withInput();
        }

        // ✅ PREREQUISITE PLACEMENT VALIDATION
        $targetOrder = $this->termOrderFor(
            (int) $academic->curriculum_id,
            $data['display_year_level'] ?? null,
            $data['display_term_no'] ?? null
        );

        $prereqIds = DB::table('subject_prerequisites')
            ->where('subject_id', $subjectId)
            ->pluck('prerequisite_subject_id')
            ->values();

        if ($prereqIds->isNotEmpty()) {
            $studentPlacement = $this->studentSubjectOrderMap($academic);

            $missing = $prereqIds->reject(
                fn ($id) => isset($studentPlacement[$id]) && $studentPlacement[$id] < $targetOrder
            );

            if ($missing->isNotEmpty()) {
                $list = Subject::whereIn('id', $missing)->orderBy('code')->get()
                    ->map(fn ($s) => "- {$s->code} — {$s->name}")
                    ->implode("\n");

                return back()->withErrors([
                    'subject_id' =>
                        "Prerequisite requirement not satisfied.\n\n" .
                        "Missing prerequisite(s):\n{$list}"
                ])->withInput();
            }
        }

        $data['student_academic_id'] = $academic->id;
        $data['evaluated_by']        = auth()->id();
        $data['evaluated_at']        = now();

        CustomStudentCurriculumSubject::create($data);

        return back()->with('success', 'Custom subject added successfully.');
    }

    /**
     * Update one custom subject row (status, remarks, display Y/T, etc.).
     */
  public function updateCustom(Request $request, User $student, CustomStudentCurriculumSubject $custom)
{
    $academic = StudentAcademic::where('user_id', $student->id)->firstOrFail();
    abort_if($custom->student_academic_id !== $academic->id, 404);

    // Allow program_admin to edit these fields (since this is the admin curriculum screen)
    $data = $request->validate([
        'subject_id'         => ['required', 'exists:subjects,id'],
        'display_year_level' => ['nullable', 'integer', 'min:1', 'max:10'],
        'display_term_no'    => ['nullable', 'integer', 'min:1', 'max:10'],
        'status'             => ['required', 'in:not_taken,enrolled,passed,failed,credited'],
        'remarks'            => ['nullable', 'string', 'max:65535'],
        'external_units'     => ['required', 'numeric', 'min:0.5', 'max:30'],
        'subject_type'       => ['nullable', 'in:major,minor,elective,general,thesis,internship'],
    ]);

    $subjectId = (int) $data['subject_id'];

    // ✅ DUPLICATE CHECK (exclude this row)
    $existsOfficial = DB::table('student_curriculum_subjects as scs')
        ->join('curriculum_term_subjects as cts', 'cts.id', '=', 'scs.curriculum_term_subject_id')
        ->where('scs.student_academic_id', $academic->id)
        ->where('cts.subject_id', $subjectId)
        ->exists();

    $existsOtherCustom = DB::table('custom_student_curriculum_subjects')
        ->where('student_academic_id', $academic->id)
        ->where('subject_id', $subjectId)
        ->where('id', '!=', $custom->id)
        ->exists();

    if ($existsOfficial || $existsOtherCustom) {
        $subject = Subject::find($subjectId);

        return back()->withErrors([
            'subject_id' =>
                "Unable to update.\n\n" .
                "Selected Subject: {$subject->code} — {$subject->name}\n\n" .
                "Reason: This subject already exists in the student's curriculum."
        ])->withInput();
    }

    // ✅ PREREQUISITE PLACEMENT CHECK (same idea as storeCustom)
    $targetOrder = $this->termOrderFor(
        (int) $academic->curriculum_id,
        $data['display_year_level'] ?? null,
        $data['display_term_no'] ?? null
    );

    $prereqIds = DB::table('subject_prerequisites')
        ->where('subject_id', $subjectId)
        ->pluck('prerequisite_subject_id')
        ->values();

    if ($prereqIds->isNotEmpty()) {
        $studentPlacement = $this->studentSubjectOrderMap($academic);

        $missing = $prereqIds->reject(
            fn ($id) => isset($studentPlacement[$id]) && $studentPlacement[$id] < $targetOrder
        );

        if ($missing->isNotEmpty()) {
            $list = Subject::whereIn('id', $missing)->orderBy('code')->get()
                ->map(fn ($s) => "- {$s->code} — {$s->name}")
                ->implode("\n");

            return back()->withErrors([
                'subject_id' => "Prerequisite requirement not satisfied.\n\nMissing prerequisite(s):\n{$list}"
            ])->withInput();
        }
    }

    $custom->update([
        ...$data,
        'evaluated_by' => Auth::id(),
        'evaluated_at' => now(),
    ]);

    return back()->with('success', 'Custom subject updated successfully.');
}



    /**
     * Delete one custom curriculum subject row.
     */
    public function destroyCustom(User $student, CustomStudentCurriculumSubject $custom)
{
    $academic = StudentAcademic::where('user_id', $student->id)->firstOrFail();
    abort_if($custom->student_academic_id !== $academic->id, 404);

    if ($this->isLockedStatus($custom->status)) {
        return back()->withErrors([
            'custom' => "You cannot remove this subject because it is already '{$custom->status}'.",
        ]);
    }

    $custom->delete();

    return back()->with('success', 'Custom curriculum subject removed.');
}


    private function isLockedStatus(?string $status): bool
{
    return in_array($status, ['enrolled', 'passed', 'failed'], true);
}

private function isProgramAdmin(): bool
{
    // Adjust to your real role system
    // e.g. return auth()->user()->role === 'program_admin';
    return auth()->user()?->role === 'program_admin';
}

private function isStudentUser(): bool
{
    // Adjust to your real role system
    return auth()->user()?->role === 'student';
}

}
