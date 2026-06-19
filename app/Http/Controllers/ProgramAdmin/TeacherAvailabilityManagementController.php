<?php

namespace App\Http\Controllers\ProgramAdmin;

use App\Http\Controllers\Controller;
use App\Models\TeacherAvailability;
use App\Models\TeacherLoadSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class TeacherAvailabilityManagementController extends Controller
{
    private function assertProgramAdmin(): void
    {
        $user = auth()->user();

        if (!method_exists($user, 'hasRole') || !$user->hasRole('program_admin')) {
            abort(403, 'Only Program Admin can access this.');
        }

        if (!$user->program_id) {
            abort(403, 'Program Admin has no assigned program.');
        }
    }

    private function assertSameProgram(User $teacher): void
    {
        $this->assertProgramAdmin();

        $admin = auth()->user();

        if (!$teacher->program_id || (int) $teacher->program_id !== (int) $admin->program_id) {
            abort(403, 'Teacher is not in your program.');
        }

        if (!method_exists($teacher, 'hasRole') || !$teacher->hasRole('teacher')) {
            abort(404, 'User is not a teacher.');
        }
    }

    /**
     * Map availability day string to class_meetings.day_of_week (tinyint).
     * Adjust if your system uses 0..6 instead of 1..7.
     */
    private function dayToDow(string $day): int
    {
        return match ($day) {
            'Monday'    => 1,
            'Tuesday'   => 2,
            'Wednesday' => 3,
            'Thursday'  => 4,
            'Friday'    => 5,
            'Saturday'  => 6,
            'Sunday'    => 7,
            default     => 0,
        };
    }

    /**
     * Return ACTIVE meetings for a teacher on a given day_of_week.
     * Uses joins to ensure we only consider active offerings.
     * If you want to include other statuses, adjust the where('co.status','active').
     */
    private function meetingsForTeacherOnDow(int $teacherId, int $dow)
    {
        return DB::table('class_meetings as cm')
            ->join('class_offerings as co', 'co.id', '=', 'cm.class_offering_id')
            ->where('cm.teacher_id', $teacherId)
            ->where('cm.day_of_week', $dow)
            ->whereNull('cm.deleted_at')
            ->where('co.status', 'active')
            ->select([
                'cm.time_start',
                'cm.time_end',
                'cm.class_offering_id',
            ])
            ->orderBy('cm.time_start')
            ->get();
    }

    private function normalizeTime(string $time): string
    {
        // request comes as HH:MM, DB is typically HH:MM:SS
        return strlen($time) === 5 ? $time . ':00' : $time;
    }

    private function formatMeetingTimes($meetings): string
    {
        return $meetings
            ->map(fn ($m) => substr($m->time_start, 0, 5) . '–' . substr($m->time_end, 0, 5))
            ->unique()
            ->implode(', ');
    }

    // ======================
    // Views
    // ======================

    public function index()
    {
        $this->assertProgramAdmin();
        $admin = auth()->user();

        $teachers = User::query()
            ->where('program_id', $admin->program_id)
            ->whereHas('roles', fn ($q) => $q->where('name', 'teacher'))
            ->withCount('teacherAvailabilities')
            ->with('teacherLoadSetting')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        // ✅ your folder is: resources/views/program-admin/...
        return view('program-admin.teacher_availability.index', compact('teachers'));
    }

    public function show(User $teacher)
    {
        $this->assertSameProgram($teacher);

        $availabilities = TeacherAvailability::where('user_id', $teacher->id)
            ->orderByRaw("FIELD(day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')")
            ->orderBy('start_time')
            ->get();

              $setting = TeacherLoadSetting::firstOrCreate(
        ['user_id' => $teacher->id],
        ['employment_type' => 'regular', 'max_units' => 36]
    );

        return view('program-admin.teacher_availability.show', compact('teacher', 'availabilities'));
    }

    public function create(User $teacher)
    {
        $this->assertSameProgram($teacher);

        $daysWithAvailability = TeacherAvailability::where('user_id', $teacher->id)
            ->pluck('day')
            ->toArray();

        return view('program-admin.teacher_availability.create', compact('teacher', 'daysWithAvailability'));
    }

    // ======================
    // Actions
    // ======================

    public function store(Request $request, User $teacher)
    {
        $this->assertSameProgram($teacher);

        $request->validate([
            'day' => [
                'required',
                Rule::in(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday']),
                Rule::unique('teacher_availabilities', 'day')
                    ->where(fn ($q) => $q->where('user_id', $teacher->id)),
            ],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time'   => ['required', 'date_format:H:i', 'after:start_time'],
        ], [
            'day.unique' => 'Availability for this day is already set. You can edit it instead.',
        ]);

        $dow = $this->dayToDow($request->day);
        $meetings = $this->meetingsForTeacherOnDow($teacher->id, $dow);

        // ✅ Clear error message: day already has scheduled classes
        if ($meetings->isNotEmpty()) {
            $times = $this->formatMeetingTimes($meetings);

            return back()
                ->withInput()
                ->withErrors([
                    'availability' =>
                        "Cannot add availability for {$request->day}. "
                        . "This teacher already has scheduled classes at {$times}. "
                        . "Please set the availability to fully cover these times (or reschedule the classes first).",
                ]);
        }

        TeacherAvailability::create([
            'user_id' => $teacher->id,
            'day' => $request->day,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
        ]);

        return redirect()
            ->route('program-admin.teacher-availabilities.show', $teacher)
            ->with('success', 'Availability added successfully.');
    }

    public function edit(User $teacher, TeacherAvailability $availability)
    {
        $this->assertSameProgram($teacher);

        if ((int) $availability->user_id !== (int) $teacher->id) {
            abort(404);
        }

        return view('program-admin.teacher_availability.edit', compact('teacher', 'availability'));
    }

    public function update(Request $request, User $teacher, TeacherAvailability $availability)
    {
        $this->assertSameProgram($teacher);

        if ((int) $availability->user_id !== (int) $teacher->id) {
            abort(404);
        }

        $request->validate([
            'start_time' => ['required', 'date_format:H:i'],
            'end_time'   => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        $dow = $this->dayToDow($availability->day);
        $meetings = $this->meetingsForTeacherOnDow($teacher->id, $dow);

        // ✅ Block shrinking availability if it would exclude existing meetings
        if ($meetings->isNotEmpty()) {
            $minStart = $meetings->min('time_start'); // HH:MM:SS
            $maxEnd   = $meetings->max('time_end');   // HH:MM:SS

            $newStart = $this->normalizeTime($request->start_time);
            $newEnd   = $this->normalizeTime($request->end_time);

            if ($newStart > $minStart || $newEnd < $maxEnd) {
                $times = $this->formatMeetingTimes($meetings);

                return back()
                    ->withInput()
                    ->withErrors([
                        'availability' =>
                            "Cannot update availability for {$availability->day}. "
                            . "Existing classes are scheduled at {$times}. "
                            . "Availability must fully cover these class times.",
                    ]);
            }
        }

        $availability->update([
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
        ]);

        return redirect()
            ->route('program-admin.teacher-availabilities.show', $teacher)
            ->with('success', 'Availability updated successfully.');
    }

    public function destroy(User $teacher, TeacherAvailability $availability)
    {
        $this->assertSameProgram($teacher);

        if ((int) $availability->user_id !== (int) $teacher->id) {
            abort(404);
        }

        $dow = $this->dayToDow($availability->day);
        $meetings = $this->meetingsForTeacherOnDow($teacher->id, $dow);

        // ✅ Block delete if teacher has active scheduled classes on that day
        if ($meetings->isNotEmpty()) {
            $times = $this->formatMeetingTimes($meetings);

            return back()->withErrors([
                'availability' =>
                    "Cannot delete availability for {$availability->day}. "
                    . "This teacher has scheduled classes at {$times}. "
                    . "Please reschedule or remove the classes first.",
            ]);
        }

        $availability->delete();

        return redirect()
            ->route('program-admin.teacher-availabilities.show', $teacher)
            ->with('success', 'Availability deleted successfully.');
    }
}
