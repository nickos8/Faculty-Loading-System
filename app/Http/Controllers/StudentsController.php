<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class StudentsController extends Controller
{
    // List pending freshmen applicants (program_id is chosen at registration)
    public function pendingIndex(Request $request)
    {
        // Basic filter: pending users who picked a program
        // If you have roles, you can add a whereHas('roles', ...) filter for 'student'
        $pending = User::query()
            ->where('status', 'pending')              // adjust if your workflow uses another field/value
            ->whereNotNull('program_id')
            ->orderBy('id', 'asc')
            ->paginate(15);

        return view('students.pending', compact('pending'));
    }
}
