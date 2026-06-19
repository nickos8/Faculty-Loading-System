<?php

namespace App\Http\Controllers\ProgramAdmin;

use App\Http\Controllers\Controller;
use App\Models\TeacherLoadSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeacherLoadSettingController extends Controller
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

    private function assertSameProgramTeacher(User $teacher): void
    {
        $this->assertProgramAdmin();
        $admin = auth()->user();

        if ((int)$teacher->program_id !== (int)$admin->program_id) {
            abort(403, 'Teacher is not in your program.');
        }

        if (!method_exists($teacher, 'hasRole') || !$teacher->hasRole('teacher')) {
            abort(404, 'User is not a teacher.');
        }
    }

    /**
     * Show settings page (or section) for a teacher.
     */
    public function show(User $teacher)
    {
        $this->assertSameProgramTeacher($teacher);

        $setting = TeacherLoadSetting::firstOrCreate(
            ['user_id' => $teacher->id],
            [
                'employment_type' => 'regular',
                'max_units' => 36,
            ]
        );

        return view('program-admin.teacher_load_settings.show', compact('teacher', 'setting'));
    }

    /**
     * Save/update the teacher load settings.
     */
    public function update(Request $request, User $teacher)
    {
        $this->assertSameProgramTeacher($teacher);

        $validated = $request->validate([
            'employment_type' => ['required', Rule::in(['regular', 'part_time'])],
            'max_units'       => ['required', 'numeric', 'min:1', 'max:60'],
        ]);

        // Automatically set max_units based on employment type
        $validated['max_units'] = $validated['employment_type'] === 'regular' ? 36 : 20;

        // Optional: Add validation to ensure the submitted value matches expected value
        if ((int)$request->input('max_units') !== $validated['max_units']) {
            return back()->withErrors([
                'max_units' => 'Invalid max units for selected employment type.',
            ])->withInput();
        }

        TeacherLoadSetting::updateOrCreate(
            ['user_id' => $teacher->id],
            $validated
        );

        return back()->with('success', 'Teacher load settings saved successfully.');
    }
}
