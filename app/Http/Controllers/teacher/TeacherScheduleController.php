<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassMeeting;
use App\Models\ClassOffering;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class TeacherScheduleController extends Controller
{
    /**
     * Show the teacher's active schedule.
     */
    public function index(Request $request)
{
    $teacher = Auth::user();
    $today   = now()->toDateString();

    // Filter: week (default) or month
    $range = $request->query('range', 'week'); // 'week' | 'month'

    // Get all active meetings for this teacher
    $meetings = ClassMeeting::query()
        ->with([
            'offering.section',
            'offering.curriculumTermSubject.subject',
            'room',
        ])
        ->where('teacher_id', $teacher->id)
        ->whereHas('offering', function ($q) use ($today) {
            $q->where('status', 'active')
              ->whereDate('end_date', '>=', $today);
        })
        ->orderBy('day_of_week')
        ->orderBy('time_start')
        ->get();

    // Map numeric day_of_week to label
    $days = [
        1 => 'Mon',
        2 => 'Tue',
        3 => 'Wed',
        4 => 'Thu',
        5 => 'Fri',
        6 => 'Sat',
        7 => 'Sun',
    ];



    // --- TEACHING LOAD CALCULATION ---

    // Total minutes per week (sum of each meeting's duration)
    $totalMinutesWeek = $meetings->sum(function ($meeting) {
        $start = Carbon::parse($meeting->time_start);
        $end   = Carbon::parse($meeting->time_end);

        return $start->diffInMinutes($end);
    });

    // Convert to hours + minutes
    $weeklyHours = intdiv($totalMinutesWeek, 60);
    $weeklyMinutesRemainder = $totalMinutesWeek % 60;

    // Simple approximation: monthly = weekly * 4 (you can change factor if you like)
    $totalMinutesMonth = $totalMinutesWeek * 4;

    $monthlyHours = intdiv($totalMinutesMonth, 60);
    $monthlyMinutesRemainder = $totalMinutesMonth % 60;

    return view('teacher.schedule.index', compact(
        'teacher',
        'meetings',
        'days',
        'range',
        'weeklyHours',
        'weeklyMinutesRemainder',
        'monthlyHours',
        'monthlyMinutesRemainder'
    ));


}

public function downloadPdf(Request $request)
{
    $teacher = Auth::user();
    $today   = now()->toDateString();

    // same filter you already have (week/month label)
    $range = $request->query('range', 'week');

    // same query as index()
    $meetings = ClassMeeting::query()
        ->with([
            'offering.section',
            'offering.curriculumTermSubject.subject',
            'room',
        ])
        ->where('teacher_id', $teacher->id)
        ->whereHas('offering', function ($q) use ($today) {
            $q->where('status', 'active')
              ->whereDate('end_date', '>=', $today);
        })
        ->orderBy('day_of_week')
        ->orderBy('time_start')
        ->get();

    $days = [
        1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu',
        5 => 'Fri', 6 => 'Sat', 7 => 'Sun',
    ];

    // same teaching load calc you already do
    $totalMinutesWeek = $meetings->sum(function ($meeting) {
        $start = Carbon::parse($meeting->time_start);
        $end   = Carbon::parse($meeting->time_end);
        return $start->diffInMinutes($end);
    });

    $weeklyHours = intdiv($totalMinutesWeek, 60);
    $weeklyMinutesRemainder = $totalMinutesWeek % 60;

    $totalMinutesMonth = $totalMinutesWeek * 4;
    $monthlyHours = intdiv($totalMinutesMonth, 60);
    $monthlyMinutesRemainder = $totalMinutesMonth % 60;

    // render blade -> pdf
    $pdf = Pdf::loadView('teacher.schedule.pdf', compact(
        'teacher',
        'meetings',
        'days',
        'range',
        'weeklyHours',
        'weeklyMinutesRemainder',
        'monthlyHours',
        'monthlyMinutesRemainder'
    ))->setPaper('a4', 'portrait');

    $filename = 'teacher_schedule_' . $teacher->id . '_' . now()->format('Y-m-d') . '.pdf';

    return $pdf->stream($filename);
}



    /**
     * Show the students enrolled in a specific class offering.
     */    /**
     * Show the students enrolled in a specific class offering.
     */
    public function students(ClassOffering $classOffering)
    {
        $teacher = Auth::user();

        // Make sure this teacher is actually assigned to this class offering
        $teachesThis = ClassMeeting::where('class_offering_id', $classOffering->id)
            ->where('teacher_id', $teacher->id)
            ->exists();

        if (! $teachesThis) {
            abort(403, 'You are not assigned to this class.');
        }

        // Load related data (section, subject, meetings for this teacher)
        $classOffering->load([
            'section',
            'curriculumTermSubject.subject',
            'meetings' => function ($q) use ($teacher) {
                $q->where('teacher_id', $teacher->id)
                  ->orderBy('day_of_week')
                  ->orderBy('time_start');
            },
        ]);

        // 1) REGULAR STUDENTS OF THE SECTION
        //    These come from student_academics.section_id
        $regularStudents = User::whereHas('studentAcademic', function ($q) use ($classOffering) {
                $q->where('section_id', $classOffering->section_id)
                  ->where('enrollment_status', 'enrolled');
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        // 2) ADDITIONAL / EXTRA-SCHEDULED STUDENTS
        //    These come from student_class_enrollments (your existing behavior)
        $additionalStudents = $classOffering->students()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        // Optional: avoid duplicates if a student is already a regular member
        $additionalStudents = $additionalStudents->filter(function ($student) use ($classOffering) {
            $acad = $student->studentAcademic;
            return ! $acad || $acad->section_id !== $classOffering->section_id;
        });

        return view(
            'teacher.schedule.students',
            compact('classOffering', 'regularStudents', 'additionalStudents')
        );
    }

}

