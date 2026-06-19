<?php
namespace App\Http\Controllers;

use App\Models\TeacherAvailability;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class TeacherAvailabilityController extends Controller
/*
public function index()
{
    $user = auth()->user(); // Get the current authenticated user

    // Filter subjects based on program_id matching the current user's program_id
    $subjects = Subject::where('program_id', $user->program_id)->get();

    return view('subjects.index', compact('subjects'));
}*/

{
    // Show all availabilities
    public function index()
{
    $user = auth()->user(); // Get the current authenticated user

    // Filter availabilities based on user_id
    $availabilities = TeacherAvailability::where('user_id', $user->id)->get();

    return view('teacher_availability.index', compact('availabilities'));
}


    // Show form to create availability
    public function create()
{
    $user = auth()->user(); // Get the current authenticated user

    // Get the days that the user already has availability for
    $daysWithAvailability = TeacherAvailability::where('user_id', $user->id)
                                                ->pluck('day')
                                                ->toArray();

    return view('teacher_availability.create', compact('daysWithAvailability'));
}


    // Store new availability
        public function store(Request $request)
    {
        // Validate the incoming data
        $request->validate([
            'day' => 'required|string',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        // Get the current authenticated user
        $user = auth()->user();

        // Store a new availability record with the user's ID
        TeacherAvailability::create([
            'user_id' => $user->id,  // Assign the current user's ID
            'day' => $request->day,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
        ]);

        return redirect()->route('teacher_availability.index');
    }

    // Show form to edit availability
  public function edit($id)
{
    // Get the current authenticated user
    $user = auth()->user();

    // Find the availability record by ID, ensuring it belongs to the current user
    $availability = TeacherAvailability::where('user_id', $user->id)->findOrFail($id);

    return view('teacher_availability.edit', compact('availability'));
}


    // Update availability
  public function update(Request $request, $id)
{


    // Get the current authenticated user
    $user = auth()->user();

    // Find the availability record by ID for the current authenticated user
    $availability = TeacherAvailability::where('user_id', $user->id)->findOrFail($id);

    // Update the availability record
    $availability->update([
        'start_time' => $request->start_time,
        'end_time' => $request->end_time,
    ]);

    // Redirect back to the index page after the update
    return redirect()->route('teacher_availability.index');
}


    // Delete availability
    public function destroy($id)
    {
        $availability = TeacherAvailability::findOrFail($id);
        $availability->delete();
        return redirect()->route('teacher_availability.index');
    }
}
