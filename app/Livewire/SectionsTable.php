<?php

namespace App\Livewire;

use App\Models\Section;
use Livewire\Component;
use Livewire\WithPagination;

class SectionsTable extends Component
{
    use WithPagination;

    /**
     * Search term (search-as-you-type).
     * Empty string = show all.
     */
    public string $q = '';

    /**
     * Status filter: 'active' | 'archived' | 'all'
     * Default hides archived.
     */
    public string $status = 'active';

    /**
     * Results per page.
     */
    public int $perPage = 15;

    /**
     * Keep filters in the URL (so refresh/back button works nicely).
     * - omit q when empty
     * - omit status when it's the default ('active')
     */
    protected $queryString = [
        'q'      => ['except' => ''],
        'status' => ['except' => 'active'],
    ];

    /**
     * When filters change, reset to page 1 so pagination doesn’t get stuck.
     */
    public function updatedQ(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();
        $pid  = $user?->program_id;

        // Safety: if user has no program, show empty result set
        if (empty($pid)) {
            $sections = Section::whereRaw('1=0')->paginate($this->perPage);
            $counts   = ['active' => 0, 'archived' => 0, 'all' => 0];

            return view('livewire.sections-table', compact('sections', 'counts'));
        }

        // Base query: only this user's program
        $base = Section::where('program_id', $pid);

        // Search-as-you-type: contains match on name
        if (trim($this->q) !== '') {
            $term = $this->q;
            $base->where('name', 'like', "%{$term}%");
        }

        // Status filter (default = active)
        if ($this->status !== 'all') {
            $base->where('status', $this->status);
        }

        // Sorted, paginated results
        $sections = $base
            ->orderBy('year_level')
            ->orderBy('term_no')
            ->orderBy('name')
            ->paginate($this->perPage);

        // Tab counts
        $counts = [
            'active'   => Section::where('program_id', $pid)->where('status', 'active')->count(),
            'archived' => Section::where('program_id', $pid)->where('status', 'archived')->count(),
            'all'      => Section::where('program_id', $pid)->count(),
        ];

        return view('livewire.sections-table', compact('sections', 'counts'));
    }
}
