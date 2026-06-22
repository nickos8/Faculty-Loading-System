<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Models\Role;
use App\Models\Program;
use App\Models\Section;
use App\Models\StudentAcademic;
use App\Notifications\AccountDecisionNotification;
use App\Models\Curriculum; // only if you want to show curriculum name in the email
use Illuminate\Support\Facades\Storage;
use App\Models\UserDocument;
use Illuminate\Support\Facades\Notification;
use App\Models\CurriculumTermSubject;
use App\Models\StudentCurriculumSubject;



class ApprovalController extends Controller
{
    public function index(Request $request)
{
    $me = $request->user();

    // Filters
    $search   = trim((string) $request->query('search', ''));
    $role     = (string) $request->query('role', 'all');      // all|teacher|program_admin|student
    $sort     = (string) $request->query('sort', 'latest');   // latest|oldest|name_asc|name_desc
    $perPage  = (int) $request->query('per_page', 15);

    // Allowed per-page values
    if (! in_array($perPage, [10, 15, 25, 50, 100], true)) {
        $perPage = 15;
    }

    // Safe empty paginators by default
    $pendingStaff = User::query()->whereRaw('1 = 0')->paginate($perPage, ['*'], 'staff_page');
    $pendingStudents = User::query()->whereRaw('1 = 0')->paginate($perPage, ['*'], 'student_page');

    /*
    |--------------------------------------------------------------------------
    | SUPER ADMIN: Pending staff
    |--------------------------------------------------------------------------
    */
    if ($me->hasRole('super_admin')) {
        $pendingStaffQuery = User::query()
            ->where('status', 'pending')
            ->whereHas('roles', function ($q) use ($role) {
                if (in_array($role, ['teacher', 'program_admin'], true)) {
                    $q->where('roles.name', $role);
                } else {
                    $q->whereIn('roles.name', ['teacher', 'program_admin']);
                }
            })
            ->with(['roles', 'documents', 'teacherLoadSetting']);

        // Search
        if ($search !== '') {
            $pendingStaffQuery->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('school_id', 'like', "%{$search}%");
            });
        }

        // Sorting
        switch ($sort) {
            case 'oldest':
                $pendingStaffQuery->oldest('id');
                break;
            case 'name_asc':
                $pendingStaffQuery->orderBy('first_name')->orderBy('last_name');
                break;
            case 'name_desc':
                $pendingStaffQuery->orderByDesc('first_name')->orderByDesc('last_name');
                break;
            case 'latest':
            default:
                $pendingStaffQuery->latest('id');
                break;
        }

        $pendingStaff = $pendingStaffQuery
            ->paginate($perPage, ['*'], 'staff_page')
            ->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | PROGRAM ADMIN: Pending students in own program
    |--------------------------------------------------------------------------
    */
    if ($me->hasRole('program_admin')) {
        $pendingStudentsQuery = User::query()
            ->where('status', 'pending')
            ->where('program_id', $me->program_id)
            ->whereHas('roles', function ($q) {
                $q->where('roles.name', 'student');
            })
            ->with(['roles', 'documents']);

        // Search
        if ($search !== '') {
            $pendingStudentsQuery->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('school_id', 'like', "%{$search}%");
            });
        }

        // Optional student-only role filter support
        if ($role === 'student' || $role === 'all') {
            // no extra condition needed
        }

        // Sorting
        switch ($sort) {
            case 'oldest':
                $pendingStudentsQuery->oldest('id');
                break;
            case 'name_asc':
                $pendingStudentsQuery->orderBy('first_name')->orderBy('last_name');
                break;
            case 'name_desc':
                $pendingStudentsQuery->orderByDesc('first_name')->orderByDesc('last_name');
                break;
            case 'latest':
            default:
                $pendingStudentsQuery->latest('id');
                break;
        }

        $pendingStudents = $pendingStudentsQuery
            ->paginate($perPage, ['*'], 'student_page')
            ->withQueryString();
    }

    return view('admin.approvals.index', [
        'pendingStaff'    => $pendingStaff,
        'pendingStudents' => $pendingStudents,
        'search'          => $search,
        'role'            => $role,
        'sort'            => $sort,
        'perPage'         => $perPage,
    ]);
}

    /**
     * Approve a user.
     * - Program Admins can only approve students from their program.
     * - Super Admins can approve any pending user.
     */
public function approve(User $user, Request $request)
{
    // 0) optional note
    $data = $request->validate(['note' => ['nullable','string','max:500']]);
    $note  = $data['note'] ?? null;
    $actor = $request->user();

    // 1) same-program gate
    if (isset($actor->program_id, $user->program_id) &&
        (int)$actor->program_id !== (int)$user->program_id) {
        return back()->withErrors(['auth' => 'You can only act on users within your program.']);
    }

    // variables we need outside the transaction
    $section   = null;
    $program   = null;
    $studentId = $user->id;

    DB::beginTransaction();
    try {
        // Lock the user row first (prevents double-approval)
        $student = User::lockForUpdate()->findOrFail($studentId);

        // 2) re-check status inside the TX to avoid TOCTTOU
        if ($student->status !== 'pending') {
            throw ValidationException::withMessages([
                'student' => 'This account is not pending.',
            ]);
        }

        // 3) derive role after locking (more consistent)
        $isStudent = method_exists($student, 'hasRole')
            ? $student->hasRole('student')
            : !empty($student->program_id);

        if ($isStudent) {
            if (!$student->program_id) {
                throw ValidationException::withMessages([
                    'student' => 'Student has no program selected.',
                ]);
            }

            // avoid duplicates if someone already placed them
            if (StudentAcademic::where('user_id', $student->id)->exists()) {
                throw ValidationException::withMessages([
                    'student' => 'Student already has an academic record.',
                ]);
            }

            // Lock program row and verify curriculum
            $program = Program::lockForUpdate()->findOrFail($student->program_id);
            if (!$program->curriculum_id) {
                throw ValidationException::withMessages([
                    'program' => 'Program has no present curriculum.',
                ]);
            }

            // Find least-filled active Y1/T1 section with capacity
            $sectionQuery = Section::where([
                    'program_id'    => $program->id,
                    'curriculum_id' => $program->curriculum_id,
                    'year_level'    => 1,
                    'term_no'       => 1,
                    'status'        => 'active',
                ])
                ->whereRaw("(SELECT COUNT(*) FROM student_academics sa
                             WHERE sa.section_id = sections.id
                               AND sa.enrollment_status = 'enrolled') < sections.capacity")
                ->orderByRaw("(SELECT COUNT(*) FROM student_academics sa
                               WHERE sa.section_id = sections.id
                                 AND sa.enrollment_status = 'enrolled') ASC")
                ->orderBy('name')
                ->lockForUpdate(); // keep the chosen row locked

            // NOTE: If you run MySQL 8+ or Postgres and want to skip already-locked sections:
            // $sectionQuery->lock('FOR UPDATE SKIP LOCKED'); // optional

            $section = $sectionQuery->first();

            if (!$section) {
                throw ValidationException::withMessages([
                    'section' => 'No available section. Create a new section or increase capacity.',
                ]);
            }

            // last-seat safety: re-count inside TX
            $used = StudentAcademic::where('section_id', $section->id)
                ->where('enrollment_status', 'enrolled')
                ->lockForUpdate()
                ->count();

            if ($used >= $section->capacity) {
                throw ValidationException::withMessages([
                    'section' => 'Section just filled up. Try again or add capacity.',
                ]);
            }

          // create StudentAcademic and keep the instance
$academic = StudentAcademic::create([
    'user_id'           => $student->id,
    'program_id'        => $program->id,
    'curriculum_id'     => $program->curriculum_id,
    'section_id'        => $section->id,
    'enrollment_status' => 'enrolled',
    'status'            => 'regular',
]);

// >>> HERE: initialize student_curriculum_subjects <<<
$this->initializeStudentCurriculum($academic);

        }

        // 4) call existing approval logic
        if (method_exists($student, 'approve')) {
            $student->approve($actor, $note);
        } else {
            $student->status = 'active'; // replace if your domain differs
            $student->save();
        }

        DB::commit();

        // === AFTER COMMIT: build friendly message & send the email ===
        $displayName = trim(($student->first_name ?? '').' '.($student->last_name ?? ''))
            ?: ($student->name ?? $student->email);

        $okMsg = ($section?->name)
            ? "{$displayName} is successfully approved and assigned to section {$section->name}."
            : "{$displayName} is successfully approved.";

        // Build reviewer & email meta
        $actorName = trim(($actor->first_name ?? '').' '.($actor->last_name ?? ''))
            ?: ($actor->name ?? 'Reviewer');

        $programName    = $program?->program_name ?? $program?->name ?? null;
        $curriculumName = null;
        if ($program?->curriculum_id) {
            $curr = Curriculum::find($program->curriculum_id);
            $curriculumName = $curr->name ?? $curr->title ?? $curr->code ?? null;
        }

        $ord = function (?int $n) {
            if ($n === null) return null;
            return $n % 100 >= 11 && $n % 100 <= 13
                ? $n.'th'
                : ($n % 10 === 1 ? $n.'st' : ($n % 10 === 2 ? $n.'nd' : ($n % 10 === 3 ? $n.'rd' : $n.'th')));
        };

        $meta = [
            'program_name'    => $programName,
            'curriculum_name' => $curriculumName,
            'section_name'    => $section->name ?? null,
            'year_label'      => isset($section->year_level) ? $ord((int)$section->year_level).' Year' : null,
            'term_label'      => isset($section->term_no)    ? $ord((int)$section->term_no).' Term'    : null,
        ];

        // Ensure the notification runs *after* commit
        try {
            DB::afterCommit(function () use ($student, $note, $actorName, $meta) {
                $student->notify(
                    // If your notification implements ShouldQueue + Queueable, this will be queued.
                    // You can also set $this->afterCommit() inside the Notification’s constructor.
                    new AccountDecisionNotification(
                        decision : 'approved',
                        note     : $note,
                        actorName: $actorName,
                        meta     : $meta
                    )
                );
            });
        } catch (\Throwable $mailErr) {
            report($mailErr); // Don’t block success on mail issues
        }

        return redirect()
        ->route('admin.approvals.index')
        ->with('success', $okMsg);

    } catch (ValidationException $e) {
        DB::rollBack();
        return back()->withErrors($e->errors());
    } catch (\Throwable $e) {
        DB::rollBack();
        report($e);
        return back()->withErrors(['error' => 'Unexpected error during approval.']);
    }
}

public function decline(User $user, Request $request): RedirectResponse
{


    $data  = $request->validate([
        'note' => ['nullable', 'string', 'max:500'],
    ]);
    $note  = $data['note'] ?? null;
    $actor = $request->user();

    // 0) Capture info we need BEFORE deleting anything
    $userEmail = $user->email;
    $userName  = trim(($user->first_name ?? '').' '.($user->last_name ?? ''))
        ?: ($user->name ?? $user->email);

    // 1) Build email actor + meta (program name is optional)
    $actorName = trim(($actor->first_name ?? '').' '.($actor->last_name ?? ''))
        ?: ($actor->name ?? 'Reviewer');

    $programName = null;
    if (!empty($user->program_id)) {
        // Load the relation if it isn't loaded already
        $user->loadMissing('program');
        if ($user->program) {
            $programName = $user->program->program_name
                ?? $user->program->name
                ?? null;
        }
    }

    // 2) TX: persist decline + delete all their data + delete the user
    DB::beginTransaction();
    try {
        // Reload + lock to avoid race conditions
        $user = User::lockForUpdate()->findOrFail($user->id);

        // 2.a) Persist the decision/status/logs (your existing domain logic)
        if (method_exists($user, 'decline')) {
            $user->decline($actor, $note);
        } else {
            $user->status = 'declined';
            $user->save();
        }

        // 2.b) Delete uploaded documents (DB + physical files)
        $user->loadMissing('documents');
        foreach ($user->documents as $doc) {
            if ($doc->path && Storage::disk('local')->exists($doc->path)) {
                Storage::disk('local')->delete($doc->path);
            }
            $doc->delete();
        }

        // 2.c) Delete student academic records
        StudentAcademic::where('user_id', $user->id)->delete();

        // 2.d) (Optional) delete other related stuff if you have them
        // e.g. $user->tokens()->delete();

        // 2.e) Delete the user so they can register again
        if (method_exists($user, 'forceDelete')) {
            $user->forceDelete(); // hard delete if using SoftDeletes
        } else {
            $user->delete();
        }

        DB::commit();
    } catch (\Throwable $e) {
        DB::rollBack();
        report($e);

        return back()->withErrors([
            'error' => 'Unexpected error while declining and cleaning up user data.',
        ]);
    }

    // 3) Send the decline notification AFTER commit (queue-aware, without User model)
    try {
        $notification = new AccountDecisionNotification(
            decision    : 'declined',
            note        : $note,
            actorName   : $actorName,
            meta        : ['program_name' => $programName],
            displayName : $userName,
        );

        Notification::route('mail', $userEmail)
            ->notify($notification);


    } catch (\Throwable $mailErr) {
        report($mailErr); // don't block declines on mail failures
    }

    // 4) Your normal success flash
    return redirect()
        ->route('admin.approvals.index')
        ->with('success', "Declined {$userName}.");
}




public function showDocument(User $user, UserDocument $doc)
{
    $actor = request()->user();

    // 1) Ensure doc belongs to this user
    if ((int)$doc->user_id !== (int)$user->id) {
        abort(404);
    }

    // 2) Authorization:
    //    - Program admins can only view docs of users in their program.
    //    - Super admin may bypass (if you have that role).
    $isSuperAdmin = false;
    if (method_exists($actor, 'hasRole')) {
        $isSuperAdmin = $actor->hasRole('super_admin');
    } elseif (method_exists($actor, 'roles')) {
        $isSuperAdmin = $actor->roles()->where('name', 'super_admin')->exists();
    }

    if (! $isSuperAdmin) {
        if (isset($actor->program_id, $user->program_id)
            && (int)$actor->program_id !== (int)$user->program_id) {
            abort(403, 'Not authorized to view this document.');
        }
    }

    // 3) Read and stream inline
    $fullPath = Storage::disk('local')->path($doc->path);
    if (! file_exists($fullPath)) {
        abort(404, 'Document file missing.');
    }

    return response()->file($fullPath, [
        'Content-Type'             => $doc->mime ?: 'application/pdf',
        'Content-Disposition'      => 'inline; filename="'.basename($doc->original_name).'"',
        'X-Content-Type-Options'   => 'nosniff',
    ]);
}

    /**
     * Create student_curriculum_subjects rows for this academic record.
     */
    private function initializeStudentCurriculum(StudentAcademic $academic): void
    {
        // Avoid duplicates (extra safety)
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
                'status'                     => 'not_taken', // default
            ]);
        }
    }


public function show(User $user, Request $request)
    {
        $actor = $request->user();

        // Authorization: super_admin can review anyone; program_admin must match program
        $isSuperAdmin = method_exists($actor, 'hasRole') ? $actor->hasRole('super_admin') : false;
        if (! $isSuperAdmin
            && isset($actor->program_id, $user->program_id)
            && (int)$actor->program_id !== (int)$user->program_id) {
            abort(403, 'Not authorized to review this user.');
        }

        // Eager-load proof docs
        $user->loadMissing('documents', 'roles', 'teacherLoadSetting');


        // Optional: let a query param ?doc=ID preselect the preview doc
        $selectedId = (int) $request->query('doc', 0);
        $firstDoc   = $selectedId
            ? $user->documents->firstWhere('id', $selectedId)
            : $user->documents->first();

        $program = $user->program_id ? Program::find($user->program_id) : null;

        return view('admin.approvals.show', [
            'user'     => $user,
            'program'  => $program,
            'firstDoc' => $firstDoc,
        ]);
    }

}
