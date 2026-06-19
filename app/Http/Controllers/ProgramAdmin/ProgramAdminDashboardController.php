<?php

namespace App\Http\Controllers\ProgramAdmin;

use App\Http\Controllers\Controller;
use App\Models\ClassOffering;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProgramAdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        abort_unless($user && $user->hasRole('program_admin'), 403);
        abort_unless($user->program_id, 403, 'Program admin has no assigned program.');

        $programId = $user->program_id;
        $today = now()->toDateString();

        $studentsBase = User::withRole('student')->where('program_id', $programId);

        $activeStudents = DB::table('users as u')
            ->join('user_roles as ur', 'ur.user_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'ur.role_id')
            ->join('student_academics as sa', 'sa.user_id', '=', 'u.id')
            ->where('r.name', 'student')
            ->where('u.program_id', $programId)
            ->where('sa.program_id', $programId)
            ->where('u.status', 'active')
            ->where('sa.enrollment_status', 'enrolled')
            ->distinct('u.id')
            ->count('u.id');

        $regularCount = DB::table('users as u')
            ->join('user_roles as ur', 'ur.user_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'ur.role_id')
            ->join('student_academics as sa', 'sa.user_id', '=', 'u.id')
            ->where('r.name', 'student')
            ->where('u.program_id', $programId)
            ->where('sa.program_id', $programId)
            ->where('u.status', 'active')
            ->where('sa.enrollment_status', 'enrolled')
            ->where('sa.status', 'regular')
            ->distinct('u.id')
            ->count('u.id');

        $irregularCount = DB::table('users as u')
            ->join('user_roles as ur', 'ur.user_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'ur.role_id')
            ->join('student_academics as sa', 'sa.user_id', '=', 'u.id')
            ->where('r.name', 'student')
            ->where('u.program_id', $programId)
            ->where('sa.program_id', $programId)
            ->where('u.status', 'active')
            ->where('sa.enrollment_status', 'enrolled')
            ->where('sa.status', 'irregular')
            ->distinct('u.id')
            ->count('u.id');

        $pendingApprovals = (clone $studentsBase)->where('status', 'pending')->count();

        $activeTeachers = User::withRole('teacher')
            ->where('program_id', $programId)
            ->where('status', 'active')
            ->count();

        $activeSections = Section::where('program_id', $programId)
            ->where('status', 'active')
            ->count();

        $classesToday = ClassOffering::query()
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->whereHas('section', fn ($q) => $q->where('program_id', $programId))
            ->count();

        $upcomingClasses = ClassOffering::query()
            ->select('class_offerings.*')
            ->joinSub(
                DB::table('class_meetings')
                    ->select('class_offering_id', DB::raw('MIN(time_start) as first_time'))
                    ->groupBy('class_offering_id'),
                'first_meeting',
                function ($join) {
                    $join->on('first_meeting.class_offering_id', '=', 'class_offerings.id');
                }
            )
            ->with([
                'section',
                'curriculumTermSubject.subject',
                'meetings.teacher',
            ])
            ->where('class_offerings.status', 'active')
            ->whereHas('section', fn ($q) => $q->where('program_id', $programId)) // ✅ Bug 1 fix
            ->whereDate('class_offerings.end_date', '>=', $today)
            ->orderBy('first_meeting.first_time')
            ->orderBy('class_offerings.id')
            ->limit(5)
            ->get();


        $yearlyActiveStudents = DB::table('users as u')
            ->join('user_roles as ur', 'ur.user_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'ur.role_id')
            ->join('student_academics as sa', 'sa.user_id', '=', 'u.id')
            ->where('r.name', 'student')
            ->where('u.program_id', $programId)
            ->where('sa.program_id', $programId)
            ->where('u.status', 'active')
            ->where('sa.enrollment_status', 'enrolled')
            ->selectRaw('YEAR(sa.created_at) as year, COUNT(DISTINCT u.id) as total')
            ->groupByRaw('YEAR(sa.created_at)')
            ->orderBy('year')
            ->get();

        return view('program-admin.dashboard.index', compact(
            'activeStudents',
            'regularCount',
            'irregularCount',
            'pendingApprovals',
            'activeTeachers',
            'activeSections',
            'classesToday',
            'upcomingClasses',
            'yearlyActiveStudents'
        ));
    }
}
