<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassOffering;
use App\Models\Program;
use App\Models\Room;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $me = $request->user();

        abort_unless($me && $me->hasRole('super_admin'), 403);

        $studentsBase = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'student'));

        $teachersBase = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'teacher'));

        $programAdminsBase = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'program_admin'));

        $totalStudents    = (clone $studentsBase)->count();
        $activeStudents   = (clone $studentsBase)->where('status', 'active')->count();
        $inactiveStudents = (clone $studentsBase)->where('status', 'inactive')->count();
        $pendingStudents  = (clone $studentsBase)->where('status', 'pending')->count();
        $declinedStudents = (clone $studentsBase)->where('status', 'declined')->count();

        $statusCounts = (clone $studentsBase)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $pendingTeachers = (clone $teachersBase)
            ->where('status', 'pending')
            ->count();

        $pendingProgramAdmins = (clone $programAdminsBase)
            ->where('status', 'pending')
            ->count();

        $pendingApprovals = $pendingTeachers + $pendingProgramAdmins;

        $regularQuery = DB::table('users as u')
            ->join('user_roles as ur', 'ur.user_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'ur.role_id')
            ->join('student_academics as sa', function ($join) {
                $join->on('sa.user_id', '=', 'u.id')
                    ->where('sa.enrollment_status', '=', 'enrolled');
            })
            ->where('r.name', 'student')
            ->where('u.status', 'active')
            ->where('sa.status', 'regular');

        $irregularQuery = DB::table('users as u')
            ->join('user_roles as ur', 'ur.user_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'ur.role_id')
            ->join('student_academics as sa', function ($join) {
                $join->on('sa.user_id', '=', 'u.id')
                    ->where('sa.enrollment_status', '=', 'enrolled');
            })
            ->where('r.name', 'student')
            ->where('u.status', 'active')
            ->where('sa.status', 'irregular');

        $activeStudentsRegular = (clone $regularQuery)
            ->distinct('u.id')
            ->count('u.id');

        $activeStudentsIrregular = (clone $irregularQuery)
            ->distinct('u.id')
            ->count('u.id');

        $activeStudents = $activeStudentsRegular + $activeStudentsIrregular;

        $yearlyActiveStudents = DB::table('users as u')
            ->join('user_roles as ur', 'ur.user_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'ur.role_id')
            ->join('student_academics as sa', function ($join) {
                $join->on('sa.user_id', '=', 'u.id')
                    ->where('sa.enrollment_status', '=', 'enrolled');
            })
            ->where('r.name', 'student')
            ->where('u.status', 'active')
            ->selectRaw('YEAR(sa.created_at) as year, COUNT(DISTINCT u.id) as total')
            ->groupByRaw('YEAR(sa.created_at)')
            ->orderBy('year')
            ->get();

        $activeTeachers = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'teacher'))
            ->where('status', 'active')
            ->count();

        $totalPrograms = Program::query()
            ->where('status', 'active')
            ->count();

        $activeSections = Section::query()
            ->where('status', 'active')
            ->count();

        $activeRooms = Room::query()
            ->where('status', 'available')
            ->count();

        $today = now()->toDateString();

        $classesToday = ClassOffering::query()
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
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
            ->whereDate('class_offerings.end_date', '>=', $today)
            ->orderBy('first_meeting.first_time')
            ->orderBy('class_offerings.id')
            ->limit(5)
            ->get();

            

        $enrollmentByProgram = DB::table('users as u')
            ->join('user_roles as ur', 'ur.user_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'ur.role_id')
            ->join('programs as p', 'p.id', '=', 'u.program_id')
            ->where('r.name', 'student')
            ->groupBy('u.program_id', 'p.program_name')
            ->select('p.program_name', DB::raw('COUNT(*) as total_students'))
            ->orderBy('p.program_name')
            ->get();

        $recentUsers = User::query()
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'super_admin'))
            ->where('status', 'active')
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', [
            'totalStudents'            => $totalStudents,
            'activeStudents'           => $activeStudents,
            'inactiveStudents'         => $inactiveStudents,
            'pendingStudents'          => $pendingStudents,
            'declinedStudents'         => $declinedStudents,
            'pendingApprovals'         => $pendingApprovals,
            'activeStudentsRegular'    => $activeStudentsRegular,
            'activeStudentsIrregular'  => $activeStudentsIrregular,
            'yearlyActiveStudents'     => $yearlyActiveStudents,
            'activeTeachers'           => $activeTeachers,
            'totalPrograms'            => $totalPrograms,
            'activeSections'           => $activeSections,
            'activeRooms'              => $activeRooms,
            'classesToday'             => $classesToday,
            'enrollmentByProgram'      => $enrollmentByProgram,
            'statusCounts'             => $statusCounts,
            'recentUsers'              => $recentUsers,
            'upcomingClasses'          => $upcomingClasses,
            'pendingTeachers'          => $pendingTeachers,
            'pendingProgramAdmins'     => $pendingProgramAdmins,
        ]);
    }
}
