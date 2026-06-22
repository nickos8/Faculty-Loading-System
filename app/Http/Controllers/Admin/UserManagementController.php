<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserManagementController extends Controller
{
    /**
     * List users with filters (role, program, status, search).
     */
    public function index(Request $request)
{
    $role      = $request->query('role');        // teacher|program_admin|student|null
    $programId = $request->query('program_id');  // optional
    $status    = $request->query('status', 'active'); // active|inactive|any
    $search    = $request->query('search');      // name/email/school_id

    // Normalize status to allowed values only
    if (! in_array($status, ['active','inactive','any'], true)) {
        $status = 'active';
    }

    $query = User::query()
        ->with(['roles', 'program']);

    if ($role) {
        $query->withRole($role);
    } else {
        // Only these roles by default
        $query->whereHas('roles', function ($q) {
            $q->whereIn('name', ['program_admin', 'teacher', 'student']);
        });
    }

    if ($programId) {
        $query->where('program_id', $programId);
    }

    // Only active/inactive, no pending/declined
    if ($status === 'any') {
        $query->whereIn('status', ['active','inactive']);
    } else {
        $query->where('status', $status); // active or inactive
    }

    if ($search) {
        $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhereRaw("CONCAT(first_name,' ',last_name) like ?", ["%{$search}%"])
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('school_id', 'like', "%{$search}%");
        });
    }

    $users    = $query->orderBy('last_name')->orderBy('first_name')->paginate(20)->withQueryString();
    $programs = Program::orderBy('program_name')->get();

    return view('admin.users.index', compact('users', 'programs', 'role', 'programId', 'status', 'search'));
}


    /**
     * Show create form.
     */
    public function create()
    {
        $programs = Program::where('status', 'active')->orderBy('program_name')->get();
        return view('admin.users.create', compact('programs'));
    }

    /**
     * Store a new user (teacher/program_admin/student).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name'   => ['required','string','max:255'],
            'last_name'    => ['required','string','max:255'],
            'phone_number' => ['nullable','string','max:50'],
            'address'      => ['nullable','string'],
            'gender'       => ['nullable', Rule::in(['Male','Female','Other'])],
            'email'        => ['required','email','max:255','unique:users,email'],
            'school_id'    => ['nullable','string','max:255','unique:users,school_id'],
            'program_id'   => ['nullable','integer','exists:programs,id'],
            'role'         => ['required', Rule::in(['program_admin','teacher','student'])],
            'status'       => ['nullable', Rule::in(['active','inactive'])],
        ]);

        $status = $data['status'] ?? 'active';

        // You can choose your own initial password strategy
        $plainPassword = str()->random(10);

        $user = User::create([
            'first_name'   => $data['first_name'],
            'last_name'    => $data['last_name'],
            'phone_number' => $data['phone_number'] ?? null,
            'address'      => $data['address'] ?? null,
            'gender'       => $data['gender'] ?? null,
            'email'        => $data['email'],
            'school_id'    => $data['school_id'] ?? null,
            'password'     => Hash::make($plainPassword),
            'program_id'   => $data['program_id'] ?? null,
            'status'       => $status,
            'approved_by'  => auth()->id(),
            'approved_at'  => now(),
        ]);

        $roleModel = Role::where('name', $data['role'])->firstOrFail();
        $user->roles()->sync([$roleModel->id]);

        // Optional: send email with login details or show the password once
        return redirect()
            ->route('admin.users.index', ['role' => $data['role']])
            ->with('success', "User created successfully. Initial password: {$plainPassword}");
    }

public function edit(User $user)
{
    $user->load('approvedBy');

    $programs = Program::where('status','active')->orderBy('program_name')->get();

    $availableRoles = Role::whereIn('name', ['program_admin','teacher','student'])
        ->orderBy('name')
        ->get();

    $assignedRoles = $user->roles()->pluck('name')->toArray();

    return view('admin.users.edit', [
        'user'           => $user,
        'programs'       => $programs,
        'availableRoles' => $availableRoles,
        'assignedRoles'  => $assignedRoles,
    ]);
}



    /**
     * Update user info + role.
     */
    public function update(Request $request, User $user)
{
    $data = $request->validate([
        'first_name'   => ['required','string','max:255'],
        'last_name'    => ['required','string','max:255'],
        'phone_number' => ['nullable','string','max:50'],
        'address'      => ['nullable','string'],
        'gender'       => ['nullable', Rule::in(['Male','Female','Other'])],
        'email'        => ['required','email','max:255', Rule::unique('users','email')->ignore($user->id)],
        'school_id'    => ['nullable','string','max:255', Rule::unique('users','school_id')->ignore($user->id)],
        'program_id'   => ['nullable','integer','exists:programs,id'],
        // MULTI-ROLE: roles[]
        'roles'        => ['nullable','array'],
        'roles.*'      => [Rule::in(['program_admin','teacher','student'])],
        'status'       => ['required', Rule::in(['active','inactive'])],
    ]);

    // Basic user fields
    $user->update([
        'first_name'   => $data['first_name'],
        'last_name'    => $data['last_name'],
        'phone_number' => $data['phone_number'] ?? null,
        'address'      => $data['address'] ?? null,
        'gender'       => $data['gender'] ?? null,
        'email'        => $data['email'],
        'school_id'    => $data['school_id'] ?? null,
        'program_id'   => $data['program_id'] ?? null,
        'status'       => $data['status'],
    ]);

    // ROLE HANDLING
    // If user is super_admin, we do NOT change their roles
    if ($user->hasRole('super_admin')) {
        // keep roles as-is, ignore roles[] input
    } else {
        $roleNames = $data['roles'] ?? [];

        if (empty($roleNames)) {
            throw ValidationException::withMessages([
                'roles' => 'Select at least one role for this user.',
            ]);
        }

        // Get the role IDs by name
        $roleIds = Role::whereIn('name', $roleNames)->pluck('id')->all();

        $user->roles()->sync($roleIds);
    }

    return redirect()
        ->route('admin.users.index')
        ->with('success', 'User updated successfully.');
}


    /**
     * Quick status updates (activate/deactivate, etc.).
     */
    public function updateStatus(Request $request, User $user)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['active','inactive'])],
        ]);

        $user->update(['status' => $data['status']]);

        return back()->with('success', 'User status updated.');
    }
}
