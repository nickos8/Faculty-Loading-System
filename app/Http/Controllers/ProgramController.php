<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\Curriculum;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProgramController extends Controller
{
    public function index()
    {
        // IMPORTANT: do NOT overwrite $programs after eager-loading.
        $programs = Program::with('curriculum')
            ->orderBy('program_code')
            ->paginate(15);

        return view('programs.index', compact('programs'));
    }

    public function create()
    {
        return view('programs.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'program_code'    => ['required', 'string', Rule::unique('programs', 'program_code')],
            'program_name'    => ['required', 'string'],
            'description'     => ['nullable', 'string'],
            'status'          => ['required', Rule::in(['active', 'inactive'])],
            'duration'        => ['required', 'integer', 'min:1'],
            'terms_per_year'  => ['required', 'integer', 'min:1'],

            // Optional at creation time (usually you create curriculum after)
            'curriculum_id'   => ['nullable', Rule::exists('curricula', 'id')],
        ]);

        Program::create($data);

        return redirect()->route('programs.index')->with('success', 'Program created successfully');
    }

    public function edit($program_id)
    {
        $program = Program::findOrFail($program_id);

        // Curricula tied to THIS program (curricula.program_id)
        $curricula = Curriculum::where('program_id', $program->id)
            ->orderByDesc('is_active')
            ->orderBy('code')
            ->get();

        return view('programs.edit', compact('program', 'curricula'));
    }

    public function update(Request $request, $id)
    {
        $program = Program::findOrFail($id);

        $data = $request->validate([
            'program_code'   => ['required', 'string', Rule::unique('programs', 'program_code')->ignore($program->id)],
            'program_name'   => ['required', 'string'],
            'description'    => ['nullable', 'string'],
            'status'         => ['required', Rule::in(['active', 'inactive'])],
            'duration'       => ['required', 'integer', 'min:1'],
            'terms_per_year' => ['required', 'integer', 'min:1'],

            // Make this nullable so you can still update programs even if no curriculum exists yet.
            // Also ensure the chosen curriculum belongs to this program (curricula.program_id = programs.id).
            'curriculum_id'  => [
                'nullable',
                Rule::exists('curricula', 'id')->where(function ($q) use ($program) {
                    $q->where('program_id', $program->id);
                }),
            ],
        ]);

        $program->update($data);

        return redirect()->route('programs.index')->with('success', 'Program updated successfully');
    }

    public function destroy($id)
    {
        $program = Program::findOrFail($id);
        $program->delete();

        return redirect()->route('programs.index')->with('success', 'Program deleted successfully');
    }
}
