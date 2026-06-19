<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\Program;
use App\Models\UserDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\TeacherLoadSetting;
use Illuminate\Validation\Rule;


class RegisteredUserController extends Controller
{
    // Show the registration form
    public function create()
    {
        // Fetch roles to populate the role selection dropdown in the view
        $roles = Role::all();

        // Fetch only programs that are active
        $programs = Program::where('status', 'active')->get();

        return view('auth.register', compact('roles', 'programs'));
    }

    public function store(Request $request)
    {
        // Validate the request data
        $request->validate([
            'first_name'   => ['required', 'regex:/^[A-Za-zÑñ\s]+$/'],
            'last_name'    => ['required', 'regex:/^[A-Za-zÑñ\s]+$/'],
            'phone_number' => ['required', 'regex:/^[0-9]+$/'],
            'address'      => ['required', 'string'],
            'gender'       => ['required', 'in:Male,Female,Other'],
            'email'        => ['required', 'email', 'unique:users,email'],
            'password'     => ['required', 'string', 'min:8'],
            // school_id is now OPTIONAL; accepts "123", "GC-123", or empty
            'school_id'    => ['nullable', 'regex:/^(GC-)?[0-9]+$/'],
            'role'         => ['required', 'exists:roles,id'],
            'program_id'   => ['required', 'exists:programs,id'],

            // PDF uploads (private). Up to 10 files, each ≤ 10MB, PDFs only.
            'documents'    => ['required', 'array', 'max:10'],
            'documents.*'  => ['file', 'mimes:pdf', 'max:10240'],

            'employment_type' => ['required_if:role,3', Rule::in(['regular', 'part_time'])],

        ]);

        DB::beginTransaction();

        try {
            // ==============================
            // Normalize school_id
            // ==============================
            // Accept:
            //  - "" or null        -> null in DB
            //  - "123"             -> "GC-123"
            //  - "GC-123"          -> "GC-123"
            $rawSchoolId = $request->input('school_id');

            if ($rawSchoolId === null || trim($rawSchoolId) === '') {
                $schoolId = null;
            } else {
                // Remove everything except digits
                $digits = preg_replace('/\D/', '', $rawSchoolId);

                // If there are digits, apply GC- prefix; otherwise treat as null
                $schoolId = $digits !== '' ? 'GC-' . $digits : null;
            }

            // ==============================
            // Create the user
            // ==============================
            $user = new User();
            $user->first_name   = $request->first_name;
            $user->last_name    = $request->last_name;
            $user->phone_number = $request->phone_number;
            $user->address      = $request->address;
            $user->gender       = $request->gender;
            $user->email        = $request->email;
            $user->password     = bcrypt($request->password);
            $user->status       = 'pending';             // Default to pending
            $user->program_id   = $request->program_id;  // Assign program
            $user->school_id    = $schoolId;             // normalized value (or null)
            $user->save();

            // Attach the selected role to the user
            $user->roles()->attach($request->role);

            if ((int)$request->role === 3) { // teacher role id
                $type = $request->employment_type; // regular or part_time
                $defaultMax = ($type === 'part_time') ? 20 : 36;

                TeacherLoadSetting::create([
                    'user_id' => $user->id,
                    'employment_type' => $type,
                    'max_units' => $defaultMax,
                ]);
            }


            // ==============================
            // Save uploaded PDFs privately
            // ==============================
            $files = $request->file('documents', []);

            if (!empty($files)) {
                foreach ($files as $file) {
                    // store privately: storage/app/user_docs/{user_id}/xxxx.pdf
                    $storedPath = $file->store("user_docs/{$user->id}", 'local'); // 'local' disk is private

                    UserDocument::create([
                        'user_id'       => $user->id,
                        'kind'          => null,
                        'original_name' => $file->getClientOriginalName(),
                        'mime'          => $file->getClientMimeType(),
                        'size'          => $file->getSize(),
                        'path'          => $storedPath,
                    ]);
                }
            }

            DB::commit();

            return redirect()
                ->route('login')
                ->with('message', 'User registered successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            // In production you might want to log this instead of returning raw message
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
