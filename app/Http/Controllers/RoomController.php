<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::orderBy('name')->get();
        return view('rooms.index', compact('rooms'));
    }

    public function create()
    {
        return view('rooms.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'               => ['required','string','max:255'],
            'capacity'           => ['required','integer','min:1'],
            'status'             => ['in:available,unavailable'],
            'description'        => ['nullable','string'],
            'daily_start_time'   => ['required','date_format:H:i'],
            'daily_end_time'     => ['required','date_format:H:i'],
        ]);

        // Enforce end > start
        if (strtotime($data['daily_end_time']) <= strtotime($data['daily_start_time'])) {
            throw ValidationException::withMessages([
                'daily_end_time' => 'End time must be after start time.'
            ]);
        }

        // Normalize to HH:MM:SS for DB (HTML time input sends HH:MM)
        $data['daily_start_time'] .= ':00';
        $data['daily_end_time']   .= ':00';

        Room::create($data);

        return redirect()->route('rooms.index')->with('status', 'Room created.');
    }

    public function edit(Room $room)
    {
        return view('rooms.edit', compact('room'));
    }

    public function update(Request $request, Room $room)
    {
        $data = $request->validate([
            'name'               => ['required','string','max:255'],
            'capacity'           => ['required','integer','min:1'],
            'status'             => ['required','in:available,unavailable'],
            'description'        => ['nullable','string'],
            'daily_start_time'   => ['required','date_format:H:i'],
            'daily_end_time'     => ['required','date_format:H:i'],
        ]);

        if (strtotime($data['daily_end_time']) <= strtotime($data['daily_start_time'])) {
            throw ValidationException::withMessages([
                'daily_end_time' => 'End time must be after start time.'
            ]);
        }

        $data['daily_start_time'] .= ':00';
        $data['daily_end_time']   .= ':00';

        $room->update($data);

        return redirect()->route('rooms.index')->with('status', 'Room updated.');
    }

    public function destroy(Room $room)
    {
        $room->delete();
        return redirect()->route('rooms.index')->with('status', 'Room deleted.');
    }
}
