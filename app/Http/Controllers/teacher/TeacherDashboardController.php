<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassMeeting;
use App\Models\ClassOffering;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TeacherDashboardController extends Controller
{
    public function index(Request $request)
    {
        $teacher = $request->user();

        abort_unless($teacher && $teacher->hasRole('teacher'), 403);

        $today = now()->toDateString();
        $dayOfWeek = now()->dayOfWeekIso;

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

        $classesToday = $meetings->where('day_of_week', $dayOfWeek)->count();

        $totalMinutesWeek = $meetings->sum(function ($meeting) {
            return Carbon::parse($meeting->time_start)
                ->diffInMinutes(Carbon::parse($meeting->time_end));
        });

        $weeklyHours = intdiv($totalMinutesWeek, 60);
        $weeklyMinutes = $totalMinutesWeek % 60;

        $nextClass = $meetings
            ->where('day_of_week', '>=', $dayOfWeek)
            ->sortBy(['day_of_week', 'time_start'])
            ->first();

        $activeClasses = ClassOffering::query()
            ->whereHas('meetingsAll', fn ($q) => $q->where('teacher_id', $teacher->id))
            ->where('status', 'active')
            ->count();

        $pendingEvaluations = ClassOffering::query()
            ->whereHas('meetingsAll', fn ($q) => $q->where('teacher_id', $teacher->id))
            ->where('status', 'active')
            ->doesntHave('finalization')
            ->count();

        return view('teacher.dashboard', compact(
            'teacher',
            'classesToday',
            'weeklyHours',
            'weeklyMinutes',
            'nextClass',
            'activeClasses',
            'pendingEvaluations',
            'meetings'
        ));
    }
}
