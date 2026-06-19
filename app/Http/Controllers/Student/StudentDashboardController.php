<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassOffering;
use App\Models\StudentAcademic;
use Illuminate\Http\Request;

class StudentDashboardController extends Controller
{
    public function index(Request $request)
    {
        $student = $request->user();

        abort_unless($student && $student->hasRole('student'), 403);

        $academic = StudentAcademic::with(['program', 'section', 'curriculum'])
            ->where('user_id', $student->id)
            ->first();

        $today = now()->toDateString();
        $dayOfWeek = now()->dayOfWeekIso;

        $sectionOfferings = collect();

        if ($academic?->section_id) {
            $sectionOfferings = ClassOffering::query()
                ->with([
                    'section',
                    'curriculumTermSubject.subject',
                    'meetings.teacher',
                    'meetings.room',
                ])
                ->where('section_id', $academic->section_id)
                ->where('status', 'active')
                ->whereDate('end_date', '>=', $today)
                ->get();
        }

        $extraOfferings = ClassOffering::query()
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

        $allOfferings = $sectionOfferings->concat($extraOfferings)->unique('id');

        $todayMeetings = $allOfferings
            ->flatMap(function ($offering) use ($dayOfWeek) {
                return $offering->meetings->where('day_of_week', $dayOfWeek);
            })
            ->sortBy('time_start')
            ->values();

        $currentSubjects = $allOfferings->count();

        return view('student.dashboard', compact(
            'student',
            'academic',
            'todayMeetings',
            'currentSubjects',
            'allOfferings'
        ));
    }
}
