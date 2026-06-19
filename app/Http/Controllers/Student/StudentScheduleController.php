<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassOffering;
use App\Models\StudentClassEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;


class StudentScheduleController extends Controller
{
 public function show(Request $request)
{
    $student = Auth::user();

    if (! $student) {
        abort(403, 'Not authenticated as student.');
    }

    // 1) Academic record (for header: section + program)
    $sa = $student->studentAcademic()->with(['program', 'section'])->first();

    $section = (object) [
        'id'           => $sa?->section?->id,
        'name'         => $sa?->section?->name ?? 'No section assigned',
        'program_name' => $sa?->program?->program_name ?? '—',
    ];

    $today = now()->toDateString();

    // 2) Offerings from the student's section (if any)
    $sectionOfferings = collect();

    if ($sa && $sa->section_id) {
        $sectionOfferings = ClassOffering::query()
            ->with([
                'section',
                'curriculumTermSubject.subject',
                'meetings.teacher',
                'meetings.room',
            ])
            ->where('section_id', $sa->section_id)
            ->where('status', 'active')
            ->whereDate('end_date', '>=', $today)
            ->get();
    }

    // 3) Offerings from student_class_enrollments (pivot)
    $enrollmentOfferings = ClassOffering::query()
        ->with([
            'section',
            'curriculumTermSubject.subject',
            'meetings.teacher',
            'meetings.room',
        ])
        ->where('status', 'active')
        ->whereDate('end_date', '>=', $today)
        ->whereHas('studentEnrollments', function ($q) use ($student) {
            $q->where('user_id', $student->id)
              ->where('status', 'enrolled');
        })
        ->get();

    // 4) Union both sets and remove duplicates by offering id
    $allOfferings = $sectionOfferings
        ->concat($enrollmentOfferings)
        ->unique('id');

    // 5) Build flat meetings list for the Blade
    $meetings = collect();

    foreach ($allOfferings as $offering) {
        $cts     = $offering->curriculumTermSubject;
        $subject = '—';

        if ($cts && $cts->subject) {
            $subject = $cts->subject->code . ' — ' . $cts->subject->name;
        }

        foreach ($offering->meetings as $m) {
            $meetings->push([
                'day'     => $m->day_of_week,
                'start'   => substr($m->time_start, 0, 5),
                'end'     => substr($m->time_end, 0, 5),
                'subject' => $subject,
                'teacher' => trim(($m->teacher->first_name ?? '') . ' ' . ($m->teacher->last_name ?? '')) ?: '—',
                'room'    => $m->room->name ?? '—',
            ]);
        }
    }

    // 6) Day labels
    $days = [
        1 => 'Mon',
        2 => 'Tue',
        3 => 'Wed',
        4 => 'Thu',
        5 => 'Fri',
        6 => 'Sat',
        7 => 'Sun',
    ];

    // 7) Derive enrolled subjects directly from the resolved offerings
    //    (avoids relying on student_curriculum_subjects.status which stays 'not_taken')
    $enrolledSubjects = $allOfferings
        ->map(fn ($offering) => $offering->curriculumTermSubject)
        ->filter()
        ->unique('id')
        ->values();

    $subjectsByCtsId = collect();

    return view('student.schedule.show', compact(
        'section', 'meetings', 'subjectsByCtsId', 'days', 'enrolledSubjects'
    ));
}

    public function downloadPdf(Request $request)
{
    $student = Auth::user();

    if (! $student) {
        abort(403, 'Not authenticated as student.');
    }

    // Same header data: section + program
    $sa = $student->studentAcademic()->with(['program', 'section'])->first();

    $section = (object) [
        'id' => $sa?->section?->id,
        'name' => $sa?->section?->name ?? 'no section assigned',
        'program_name' => $sa?->program?->program_name ?? '',
    ];


    $today = now()->toDateString();

    // Offerings from section (same as show())
    $sectionOfferings = collect();
    if ($sa && $sa->section_id) {
        $sectionOfferings = ClassOffering::query()
            ->with([
                'section',
                'curriculumTermSubject.subject',
                'meetings.teacher',
                'meetings.room',
            ])
            ->where('section_id', $sa->section_id)
            ->where('status', 'active')
            ->whereDate('end_date', '>=', $today)
            ->get();
    }

    // Offerings from enrollments pivot (same as show())
    $enrollmentOfferings = ClassOffering::query()
        ->with([
            'section',
            'curriculumTermSubject.subject',
            'meetings.teacher',
            'meetings.room',
        ])
        ->where('status', 'active')
        ->whereDate('end_date', '>=', $today)
        ->whereHas('studentEnrollments', function ($q) use ($student) {
            $q->where('user_id', $student->id)
              ->where('status', 'enrolled');
        })
        ->get();

    // Union + unique (same as show())
    $allOfferings = $sectionOfferings
        ->concat($enrollmentOfferings)
        ->unique('id');

    // Build flat meetings list (same as show())
    $meetings = collect();

    foreach ($allOfferings as $offering) {
        $cts     = $offering->curriculumTermSubject;
        $subject = '—';

        if ($cts && $cts->subject) {
            $subject = $cts->subject->code . ' — ' . $cts->subject->name;
        }

        foreach ($offering->meetings as $m) {
            $meetings->push([
                'day'     => $m->day_of_week,
                'start'   => substr($m->time_start, 0, 5),
                'end'     => substr($m->time_end, 0, 5),
                'subject' => $subject,
                'teacher' => trim(($m->teacher->first_name ?? '') . ' ' . ($m->teacher->last_name ?? '')) ?: '—',
                'room'    => $m->room->name ?? '—',
            ]);
        }
    }

    // Day labels (same as show())
    $days = [
        1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu',
        5 => 'Fri', 6 => 'Sat', 7 => 'Sun',
    ];

    // Optional: sort meetings for nicer PDF output
    $meetings = $meetings->sortBy([
        ['day', 'asc'],
        ['start', 'asc'],
    ])->values();

    // Render Blade -> PDF
    $pdf = Pdf::loadView('student.schedule.pdf', [
        'student' => $student,
        'section' => $section,
        'meetings' => $meetings,
        'days' => $days,
        // You can pass school header too (same as teacher PDF)
        'schoolName' => 'Granby Colleges Of Science and Technology',
        'schoolAddress' => 'Ibayo Silangan, Naic, Cavite, Philippines',
        'schoolContact' => 'Tel: (63) 111-2222 • Email: Granby@gmail.com',
        'termLabel' => 'School Year: ____________',
    ])->setPaper('a4', 'portrait');

    $filename = 'student_schedule_' . $student->id . '_' . now()->format('Y-m-d') . '.pdf';

    return $pdf->stream($filename);
}


    public function history(Request $request)
{
    $student = Auth::user();
    if (! $student) {
        abort(403, 'Not authenticated as student.');
    }

    $history = StudentClassEnrollment::query()
        ->where('user_id', $student->id)
        ->where('status', '!=', 'enrolled') // history only (Dropped/Failed/Passed/etc.)
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

    return view('student.schedule.history', compact('student', 'history', 'dayNames'));
}
}


