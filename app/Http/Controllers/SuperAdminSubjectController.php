<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuperAdminSubjectController extends Controller
{

// Display a list of all subjects
    // In the controller method
public function index(Request $request)
{
    $user = auth()->user(); // Get the current authenticated user

    // Start the query
    $query = Subject::with('creator');

    // If the user's program_id is set, fetch subjects based on the program_id
    if ($user->program_id) {
        $query->where('program_id', $user->program_id);
    }

    // If the user's program_id is null, fetch all subjects
    else {
        $query->get(); // No filtering, fetch all subjects
    }

    // If there's a search query, apply the search on name or code
    if ($request->has('search') && $request->search) {
        $searchTerm = $request->search;
        $query->where(function ($query) use ($searchTerm) {
            $query->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('code', 'like', '%' . $searchTerm . '%');
        });
    }

    // Get the filtered subjects
    $subjects = $query->get();

    // Return the view with the filtered subjects
    return view('superadminsubjects.index', compact('subjects'));
}




    // Show the form to create a new subject
    public function create()
    {
        return view('superadminsubjects.create');
    }

    // Store a new subject in the database
  public function store(Request $request)
{
    // Ensure the user is authenticated
    if (!Auth::check()) {
        return redirect()->route('login')->with('error', 'You must be logged in to create a subject.');
    }

    // Proceed with creating the subject if user is authenticated
    $validated = $request->validate([
        'name' => 'required|string',
        'code' => 'required|string',
        'units' => 'required|integer',
        'type' => 'required|string',
    ]);

    // Create subject and set 'created_by' as the authenticated user's ID
    $subject = Subject::create([
        'name' => $validated['name'],
        'code' => $validated['code'],
        'units' => $validated['units'],
        'type' => $validated['type'],
        'created_by' => $request->user()->id,
        'program_id' => $request->user()->program_id,// Default program_id, adjust as necessary


    ]);



    return redirect()->route('superadminsubjects.index');
}



    // Show the form to edit an existing subject
    public function edit(Subject $subject)
    {
        return view('superadminsubjects.edit', compact('subject'));
    }

    // Update the specified subject
    public function update(Request $request, Subject $subject)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $subject->update([
            'code' => $request->code,
            'name' => $request->name,
            'units' => $request->units,
            'type' => $request->type,


        ]);

        return redirect()->route('superadminsubjects.index');
    }

    // Delete a subject
    public function destroy(Subject $subject)
    {
        $subject->delete();
        return redirect()->route('superadminsubjects.index');
    }
}
