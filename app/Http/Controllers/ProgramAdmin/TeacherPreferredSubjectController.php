<?php

namespace App\Http\Controllers\ProgramAdmin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\TeacherPreferredSubject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TeacherPreferredSubjectController extends Controller
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

        if ((int) $teacher->program_id !== (int) $admin->program_id) {
            abort(403, 'Teacher is not in your program.');
        }

        if (!method_exists($teacher, 'hasRole') || !$teacher->hasRole('teacher')) {
            abort(404, 'User is not a teacher.');
        }
    }

    public function show(User $teacher)
    {
        $this->assertSameProgramTeacher($teacher);

        $admin = auth()->user();

        $subjects = Subject::query()
            ->where('program_id', $admin->program_id)
            ->where('status', 'active')
            ->orderBy('code')
            ->orderBy('name')
            ->get();

        $preferredSubjects = TeacherPreferredSubject::query()
            ->with('subject')
            ->where('teacher_id', $teacher->id)
            ->orderByDesc('preference_level')
            ->get()
            ->keyBy('subject_id');

        return view('program-admin.teacher_preferred_subjects.show', compact(
            'teacher',
            'subjects',
            'preferredSubjects'
        ));
    }

    public function store(Request $request, User $teacher)
    {
        $this->assertSameProgramTeacher($teacher);

        $admin = auth()->user();

        $validated = $request->validate([
            'subject_id' => [
                'required',
                'integer',
                Rule::exists('subjects', 'id')->where(function ($query) use ($admin) {
                    $query->where('program_id', $admin->program_id)
                          ->where('status', 'active');
                }),
                Rule::unique('teacher_preferred_subjects', 'subject_id')->where(function ($query) use ($teacher) {
                    $query->where('teacher_id', $teacher->id);
                }),
            ],
            'preference_level' => ['required', 'integer', 'in:1,2,3'],
        ], [
            'subject_id.unique' => 'This subject is already added to the teacher’s preferred subjects.',
        ]);

        TeacherPreferredSubject::create([
            'teacher_id' => $teacher->id,
            'subject_id' => $validated['subject_id'],
            'preference_level' => $validated['preference_level'],
        ]);

        return redirect()
            ->route('program-admin.teacher-preferred-subjects.show', $teacher)
            ->with('success', 'Preferred subject added successfully.');
    }

    public function update(Request $request, User $teacher, TeacherPreferredSubject $teacherPreferredSubject)
    {
        $this->assertSameProgramTeacher($teacher);

        if ((int) $teacherPreferredSubject->teacher_id !== (int) $teacher->id) {
            abort(404);
        }

        $validated = $request->validate([
            'preference_level' => ['required', 'integer', 'in:1,2,3'],
        ]);

        $teacherPreferredSubject->update([
            'preference_level' => $validated['preference_level'],
        ]);

        return redirect()
            ->route('program-admin.teacher-preferred-subjects.show', $teacher)
            ->with('success', 'Preference level updated successfully.');
    }

    public function destroy(User $teacher, TeacherPreferredSubject $teacherPreferredSubject)
    {
        $this->assertSameProgramTeacher($teacher);

        if ((int) $teacherPreferredSubject->teacher_id !== (int) $teacher->id) {
            abort(404);
        }

        $teacherPreferredSubject->delete();

        return redirect()
            ->route('program-admin.teacher-preferred-subjects.show', $teacher)
            ->with('success', 'Preferred subject removed successfully.');
    }

    public function sync(Request $request, User $teacher)
    {
        $this->assertSameProgramTeacher($teacher);

        $admin = auth()->user();

        $validated = $request->validate([
            'subjects' => ['nullable', 'array'],
            'subjects.*.subject_id' => [
                'required',
                'integer',
                Rule::exists('subjects', 'id')->where(function ($query) use ($admin) {
                    $query->where('program_id', $admin->program_id)
                          ->where('status', 'active');
                }),
            ],
            'subjects.*.preference_level' => ['required', 'integer', 'in:1,2,3'],
        ]);

        $items = collect($validated['subjects'] ?? []);

        $duplicateSubjectIds = $items->pluck('subject_id')
            ->duplicates()
            ->values();

        if ($duplicateSubjectIds->isNotEmpty()) {
            return back()->withErrors([
                'subjects' => 'Duplicate subjects are not allowed.',
            ])->withInput();
        }

        DB::transaction(function () use ($teacher, $items) {
            TeacherPreferredSubject::where('teacher_id', $teacher->id)->delete();

            foreach ($items as $item) {
                TeacherPreferredSubject::create([
                    'teacher_id' => $teacher->id,
                    'subject_id' => $item['subject_id'],
                    'preference_level' => $item['preference_level'],
                ]);
            }
        });

        return redirect()
            ->route('program-admin.teacher-preferred-subjects.show', $teacher)
            ->with('success', 'Teacher preferred subjects saved successfully.');
    }
}
