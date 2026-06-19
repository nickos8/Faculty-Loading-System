<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Models\User;
use App\Models\Program;
use App\Models\StudentAcademic;
use App\Models\UserApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\UpdateStudentRequest;
use \Illuminate\Validation\ValidationException;
use App\Models\CurriculumTermSubject;
use App\Models\StudentCurriculumSubject;
use App\Notifications\StudentAccountReadyNotification;
use Illuminate\Support\Facades\Log;


class StudentController extends Controller
{
public function index(Request $request)
{
    $admin = $request->user();

    if (! $admin->program_id) {
        abort(403, 'Program admin has no program assigned.');
    }

    // Search term from query string
    $search = trim((string) $request->input('search', ''));

    // Base query: students in this admin's program
    $studentsQuery = User::with(['studentAcademic.section'])
        ->where('program_id', $admin->program_id)
        ->whereIn('status', ['active', 'inactive'])
        ->whereHas('roles', function ($q) {
            $q->where('roles.id', 4); // role_id = 4 is student
        });

    // Apply search filter if there is input
    if ($search !== '') {
        $studentsQuery->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
              ->orWhere('school_id', 'like', "%{$search}%");
        });
    }

    // Sorting + pagination
    $students = $studentsQuery
        ->orderBy('last_name')
        ->orderBy('first_name')
        ->paginate(15)
        ->withQueryString();

    return view('program-admin.students.index', compact('students'));
}


    public function create(Request $request)
    {
        $admin = $request->user();

        // Guard: must have a program
        if (! $admin->program_id) {
            abort(403, 'Program admin has no program assigned.');
        }

        $program = Program::with('curriculum')
            ->findOrFail($admin->program_id);

        // Guard: program must have a curriculum configured (Case 1 strict)
        if (! $program->curriculum_id) {
            // You can redirect back with an error instead of aborting.
            abort(422, 'This program has no curriculum configured. Please contact the super admin.');
        }

        // Load active sections for this program (and maybe only for that curriculum)
        $sections = $program->sections()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('program-admin.students.create', [
            'program'  => $program,
            'sections' => $sections,
        ]);
    }

  public function store(StoreStudentRequest $request)
{
    $admin = $request->user();

    if (! $admin->program_id) {
        abort(403, 'Program admin has no program assigned.');
    }

    $program = Program::findOrFail($admin->program_id);

    if (! $program->curriculum_id) {
        return back()
            ->withErrors(['program' => 'This program has no curriculum configured.'])
            ->withInput();
    }

    $data = $request->validated();

    $user = null;

    DB::transaction(function () use ($data, $admin, $program, &$user) {
        // 1. Create user
        $user = User::create([
            'first_name'   => $data['first_name'],
            'last_name'    => $data['last_name'],
            'phone_number' => $data['phone_number'],
            'address'      => $data['address'],
            'gender'       => $data['gender'],
            'email'        => $data['email'],
            'school_id'    => $data['school_id'],
            'password'     => Hash::make($data['password']),

            'program_id'   => $program->id,
            'status'       => 'active',
            'approved_by'  => $admin->id,
            'approved_at'  => now(),
        ]);

        try {
    $user->notify(new StudentAccountReadyNotification([
        'school_id'     => $user->school_id,
        'program_name'  => $program->program_name ?? null,
        // if you want section name, you can load it from $academic->section after create
    ]));
} catch (\Throwable $e) {
    Log::error('Student account ready email failed', [
        'user_id' => $user->id ?? null,
        'email'   => $user->email ?? null,
        'error'   => $e->getMessage(),
    ]);
}


        // 2. Attach role student (role_id = 4)
        DB::table('user_roles')->insert([
            'user_id'    => $user->id,
            'role_id'    => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Log approval in user_approvals history
        UserApproval::create([
            'user_id'  => $user->id,
            'acted_by' => $admin->id,
            'decision' => 'approved',
            'note'     => 'Approved during program admin student creation.',
        ]);

        $leaveEmpty = (bool) ($data['leave_curriculum_empty'] ?? false);
        // 4. Create student_academics
        $academic = StudentAcademic::create([
            'user_id'           => $user->id,
            'program_id'        => $program->id,
            'curriculum_id'     => $leaveEmpty ? null : $program->curriculum_id,
            'section_id'        => $data['section_id'] ?? null,
            'enrollment_status' => 'enrolled',
            'status'            => $data['academic_status'] ?? 'regular',
                ]);

        // 5. Initialize student_curriculum_subjects (same behavior as ApprovalController)
        if ($academic->curriculum_id) {
    $this->initializeStudentCurriculum($academic);
}

    });

    return redirect()
        ->route('program-admin.students.create')
        ->with('success', "Student {$user->first_name} {$user->last_name} created successfully.");
}


public function edit(User $user)
{
    $admin = auth()->user();

    // 1. Ensure this user is in the same program as the admin
    if ($user->program_id !== $admin->program_id) {
        abort(403, 'You are not allowed to edit students from another program.');
    }

    // 2. Ensure this user is a student (role_id = 4)
    $isStudent = $user->roles()->where('roles.id', 4)->exists();
    if (! $isStudent) {
        abort(403, 'This user is not a student.');
    }


    // 3. Load related approver so we can display it on the edit form
    $user->loadMissing('approvedBy');

    // 3. Load student's academic record
    $academic = $user->studentAcademic;
    if (! $academic) {
        abort(404, 'Student academic record not found.');
    }

    // 4. Load current program + sections
    $program = Program::with('curriculum')->findOrFail($admin->program_id);

    $sections = $program->sections()
        ->where('status', 'active')
        ->orderBy('name')
        ->get();

    // 5. Load all active programs for transfer
    $programs = Program::where('status', 'active')
        ->orderBy('program_name')
        ->get();

    return view('program-admin.students.edit', [
        'student'  => $user,
        'academic' => $academic,
        'program'  => $program,
        'sections' => $sections,
        'programs' => $programs,  // 👈 NEW
    ]);
}


public function update(UpdateStudentRequest $request, User $user)
{
    $admin = $request->user();

    // 1. Program guard (based on current program)
    if ($user->program_id !== $admin->program_id) {
        abort(403, 'You are not allowed to edit students from another program.');
    }

    // 2. Role guard (must be student)
    $isStudent = $user->roles()->where('roles.id', 4)->exists();
    if (! $isStudent) {
        abort(403, 'This user is not a student.');
    }

    // 3. Academic record
    $academic = $user->studentAcademic;
    if (! $academic) {
        abort(404, 'Student academic record not found.');
    }

    $data = $request->validated();

    $oldProgramId = $user->program_id;
    $newProgramId = (int) $data['program_id'];
    $programChanged = $oldProgramId !== $newProgramId;
    $newProgramName = null; // ← placeholder for later


    DB::transaction(function () use ($user, $academic, $data, $programChanged, $newProgramId, &$newProgramName) {

    // Update users table
    $user->update([
        'first_name'   => $data['first_name'],
        'last_name'    => $data['last_name'],
        'phone_number' => $data['phone_number'],
        'address'      => $data['address'],
        'gender'       => $data['gender'],
        'email'        => $data['email'],
        'school_id'    => $data['school_id'],
        'status'       => $data['status'],
        'program_id'   => $newProgramId,
    ]);

    if ($programChanged) {

        // Load new program
        $newProgram = Program::findOrFail($newProgramId);

        if (! $newProgram->curriculum_id) {
            throw ValidationException::withMessages([
                'program_id' => 'Selected program has no active curriculum configured.',
            ]);
        }

        // Save program name for message
        $newProgramName = $newProgram->program_name;   // ← IMPORTANT

        // Transfer academic record
        $academic->update([
            'program_id'        => $newProgram->id,
            'curriculum_id'     => $newProgram->curriculum_id,
            'section_id'        => null,
            'status'            => $data['academic_status'],
            'enrollment_status' => $data['enrollment_status'],
        ]);

    } else {

        // no program change
        $academic->update([
            'section_id'        => $data['section_id'] ?? null,
            'status'            => $data['academic_status'],
            'enrollment_status' => $data['enrollment_status'],
        ]);
    }
});


    $studentName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

    if ($programChanged) {
        $msg = "Student {$studentName} has been successfully transferred to the {$newProgramName}.";
    } else {
        $msg = "Student {$studentName} information updated successfully.";
    }

    return redirect()
        ->route('program-admin.students.index')
        ->with('success', $msg);

}

/**
 * Build student_curriculum_subjects for a newly created StudentAcademic.
 * Mirrors the logic used in ApprovalController.
 */
private function initializeStudentCurriculum(StudentAcademic $academic): void
{
    // Extra safety: don't duplicate if something already exists
    if ($academic->curriculumSubjects()->exists()) {
        return;
    }

    // Get all curriculum_term_subjects for this student's curriculum
    $ctsList = CurriculumTermSubject::whereHas('term', function ($q) use ($academic) {
        $q->where('curriculum_id', $academic->curriculum_id);
    })->get();

    foreach ($ctsList as $cts) {
        StudentCurriculumSubject::create([
            'student_academic_id'        => $academic->id,
            'curriculum_term_subject_id' => $cts->id,
            'status'                     => 'not_taken',
        ]);
    }
}



}
