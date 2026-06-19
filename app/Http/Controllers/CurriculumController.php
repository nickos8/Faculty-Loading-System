<?php

namespace App\Http\Controllers;

use App\Models\{
    Curriculum,
    Program,
    Subject,
    CurriculumTermSubject
};
use App\Actions\Curriculum\GenerateTermGrid;
use Illuminate\Http\Request;

class CurriculumController extends Controller
{
    public function index(Request $request)
{
    $q = trim((string) $request->query('q', ''));
    $perPage = (int) $request->query('per_page', 15);
    $sort = (string) $request->query('sort', 'latest');

    if (! in_array($perPage, [10, 15, 25, 50, 100], true)) {
        $perPage = 15;
    }

    $curricula = Curriculum::query()
        ->with('program')
        ->where('program_id', auth()->user()->program_id)
        ->when($q !== '', function ($query) use ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('code', 'like', "%{$q}%")
                    ->orWhere('title', 'like', "%{$q}%")
                    ->orWhereHas('program', function ($programQuery) use ($q) {
                        $programQuery->where('program_name', 'like', "%{$q}%")
                                     ->orWhere('program_code', 'like', "%{$q}%");
                    });
            });
        });

    switch ($sort) {
        case 'oldest':
            $curricula->oldest('id');
            break;
        case 'code_asc':
            $curricula->orderBy('code');
            break;
        case 'code_desc':
            $curricula->orderByDesc('code');
            break;
        case 'latest':
        default:
            $curricula->latest('id');
            break;
    }

    $curricula = $curricula
        ->paginate($perPage)
        ->withQueryString();

    return view('curricula.index', compact('curricula', 'q', 'perPage', 'sort'));
}

    /**
     * Show the form for creating a new curriculum (restricted to user's program).
     */
    public function create()
    {
        if (! auth()->user()->program_id) {
            abort(403, 'No program assigned to your account.');
        }

        $program = Program::findOrFail(auth()->user()->program_id);

        return view('curricula.create', compact('program'));
    }

    /**
     * Store a newly created curriculum.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ]);

        $data['program_id'] = auth()->user()->program_id;

        $curriculum = Curriculum::create($data);

        // Generate term grid
        (new GenerateTermGrid)($curriculum);

        return redirect()
            ->route('curricula.show', $curriculum->id)
            ->with('success', 'Curriculum created.');
    }

    /**
     * Display the specified curriculum + prerequisite-aware subject choices.
     */
    public function show($id)
    {
        $curriculum = Curriculum::with([
                'program',
                // IMPORTANT: eager-load prerequisites for every subject shown in the grid
                'terms.subjects.subject.prerequisites',
            ])
            ->where('program_id', auth()->user()->program_id)
            ->findOrFail($id);

        // Order terms properly so "earlier terms" logic works
        $orderedTerms = $curriculum->terms
            ->sortBy(fn ($t) => ($t->year_level * 100) + $t->term_no)
            ->values();

        // Assign an "order index" per term for prerequisite checking
        $termOrder = [];
        foreach ($orderedTerms as $idx => $term) {
            $termOrder[$term->id] = $idx;
        }

        // Map each scheduled subject_id => termOrderIndex where it appears
        $subjectPlacedAt = [];
        foreach ($orderedTerms as $term) {
            foreach ($term->subjects as $cts) {
                if ($cts->subject_id) {
                    $subjectPlacedAt[$cts->subject_id] = $termOrder[$term->id];
                }
            }
        }

        // Used subject IDs (so we never add duplicates)
        $usedIds = array_keys($subjectPlacedAt);

        // Choices: subjects not used yet, active, include prerequisites
        $choices = Subject::with('prerequisites')
            ->where('status', 'active')
            ->whereNotIn('id', $usedIds)
            ->orderBy('code')
            ->get();

        // Grid by year level
        $grid = $curriculum->terms->groupBy('year_level');

        return view('curricula.show', compact(
            'curriculum',
            'grid',
            'choices',
            'termOrder',
            'subjectPlacedAt'
        ));
    }

    /**
     * Edit form (restricted).
     */
    public function edit($id)
    {
        $curriculum = Curriculum::where('program_id', auth()->user()->program_id)
            ->findOrFail($id);

        return view('curricula.edit', compact('curriculum'));
    }

    /**
     * Update a curriculum (restricted).
     */
    public function update(Request $request, $id)
    {
        $curriculum = Curriculum::where('program_id', auth()->user()->program_id)
            ->findOrFail($id);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ]);

        $curriculum->update($data);

        return redirect()
            ->route('curricula.show', $curriculum->id)
            ->with('success', 'Curriculum updated.');
    }

    /**
     * Delete a curriculum (restricted).
     */
    public function destroy($id)
    {
        $curriculum = Curriculum::where('program_id', auth()->user()->program_id)
            ->findOrFail($id);

        $curriculum->delete();

        return redirect()
            ->route('curricula.index')
            ->with('success', 'Curriculum deleted.');
    }
}
