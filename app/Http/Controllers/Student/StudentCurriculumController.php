<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StudentAcademic;
use App\Models\StudentCurriculumSubject;
use App\Models\CustomStudentCurriculumSubject;

class StudentCurriculumController extends Controller
{
    public function index(Request $request)
{
    $user = $request->user();

    $academic = StudentAcademic::with(['student', 'program', 'curriculum'])
        ->where('user_id', $user->id)
        ->firstOrFail();

    $formatOrdinal = function (?int $n) {
        if ($n === null) return null;
        return $n . match ($n % 10) {
            1 => 'st',
            2 => 'nd',
            3 => 'rd',
            default => 'th',
        };
    };

    $buildTermLabel = function (?int $year, ?int $term) use ($formatOrdinal) {
        if ($year === null && $term === null) return 'Unassigned Year / Term';

        $yearText = $year ? $formatOrdinal($year) . ' Year' : 'Year N/A';
        $termText = $term ? $formatOrdinal($term) . ' Term' : 'Term N/A';

        return $yearText . ' • ' . $termText;
    };

    // OFFICIAL
    $official = $academic->curriculumSubjects()
        ->with(['curriculumTermSubject.subject', 'curriculumTermSubject.term'])
        ->get()
        ->map(function (StudentCurriculumSubject $row) use ($buildTermLabel) {
            $cts     = $row->curriculumTermSubject;
            $subject = $cts?->subject;
            $term    = $cts?->term;

            $year   = $term?->year_level;
            $termNo = $term?->term_no;

            return (object) [
                'source'        => 'official',
                'is_custom'     => false,
                'term_sort_key' => sprintf('%02d-%02d', $year ?? 99, $termNo ?? 99),
                'term_label'    => $buildTermLabel($year, $termNo),

                'code'          => $subject->code ?? 'N/A',
                'name'          => $subject->name ?? 'Unknown subject',
                'units'         => $cts?->unit,
                'type'          => $cts?->type,
                'status'        => $row->status,
                'remarks'       => $row->remarks,
            ];
        })
        ->toBase(); // ✅ important: convert to Support\Collection

    // CUSTOM
    $custom = $academic->customCurriculumSubjects()
        ->with('subject')
        ->orderedForDisplay()
        ->get()
        ->map(function (CustomStudentCurriculumSubject $row) use ($buildTermLabel) {
            $subject = $row->subject;

            $year   = $row->display_year_level;
            $termNo = $row->display_term_no;

            $code = $subject->code
                ?? $row->external_subject_code
                ?? 'N/A';

            $name = $subject->name
                ?? $row->external_subject_name
                ?? 'Custom subject';

            return (object) [
                'source'        => 'custom',
                'is_custom'     => true,
                'term_sort_key' => sprintf('%02d-%02d', $year ?? 99, $termNo ?? 99),
                'term_label'    => $buildTermLabel($year, $termNo),

                'code'          => $code,
                'name'          => $name,
                'units'         => $row->external_units,
                'type'          => $row->subject_type,
                'status'        => $row->status,
                'remarks'       => $row->remarks,
            ];
        })
        ->toBase(); // ✅ important

    // MERGE + SORT + GROUP
    $all = $official
        ->merge($custom)
        ->sortBy([
            ['term_sort_key', 'asc'],
            ['source', 'asc'],
        ])
        ->values();

    $groupedTerms = $all->groupBy('term_label');

    return view('student.curriculum.index', [
        'academic'     => $academic,
        'groupedTerms' => $groupedTerms,
    ]);
}

}
