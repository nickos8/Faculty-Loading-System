@extends('layouts.app')

@section('content')
@php
    /** @var \Illuminate\Support\Collection $classes */
    $classes = collect($classes ?? []);

    // If day_of_week is stored as number (1-7), map to labels
    $dayMap = [
        1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'
    ];
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- HEADER (Subjects style) --}}
    <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
        <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">My Classes</h1>
                <p class="text-sm text-slate-600">
                    Select a class offering to evaluate students (Passed / Failed).
                </p>
            </div>
        </div>
    </div>

    {{-- FLASH (Subjects style) --}}
    @if (session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 shadow-sm">
            <div class="font-semibold">Success</div>
            <div class="text-sm mt-1">{{ session('success') }}</div>
        </div>
    @endif

    {{-- FILTER / SEARCH (Subjects style) --}}
    <div class="rounded-2xl border border-slate-200/70 bg-white p-6 shadow-sm">
        <div class="grid gap-4 sm:grid-cols-6">
            <div class="sm:col-span-4">
                <label class="block text-xs font-medium text-slate-500 mb-1" for="search">Search</label>
                <input id="search"
                       type="text"
                       placeholder="Search subject / section..."
                       class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                              focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                       oninput="filterRows(this.value)">
            </div>

            <div class="sm:col-span-2 flex items-end gap-2">
                <button type="button"
                        onclick="document.getElementById('search').focus()"
                        class="w-full sm:w-auto px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800">
                    Search
                </button>

                <button type="button"
                        onclick="document.getElementById('search').value=''; filterRows('');"
                        class="w-full sm:w-auto px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-medium text-slate-900 hover:bg-slate-50">
                    Clear
                </button>
            </div>
        </div>

        <div class="mt-3 text-xs text-slate-500">
            Total classes: <span class="font-medium text-slate-700">{{ $classes->count() }}</span>
        </div>
    </div>

    {{-- CONTENT --}}
    @if($classes->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center shadow-sm">
            <div class="text-sm font-semibold text-slate-900">No classes found</div>
            <div class="mt-1 text-xs text-slate-500">No classes were assigned to you yet.</div>
        </div>
    @else
        <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
            <div class="px-6 py-4 flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold text-slate-900">Class Offerings</div>
                    <div class="text-xs text-slate-500">Choose a class to open evaluation.</div>
                </div>
                <div class="text-xs text-slate-500">
                    Showing <span class="font-medium text-slate-700">{{ $classes->count() }}</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-xs sm:text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold text-slate-600">
                            <th class="px-6 py-3">Subject</th>
                            <th class="px-6 py-3">Section</th>
                            <th class="px-6 py-3">Start Date</th>
                            <th class="px-6 py-3">End Date</th>
                            <th class="px-6 py-3 text-right">Action</th>
                        </tr>
                    </thead>

                    <tbody id="classesBody" class="divide-y divide-slate-100">
                        @forelse($classes as $classOffering)

                            @php
                                $subject = optional(optional($classOffering->curriculumTermSubject)->subject);
                                $section = optional($classOffering->section);

                                // 1) Try reading from class_offerings table (common)
                                $startDateRaw = $classOffering->start_date ?? null;
                                $endDateRaw   = $classOffering->end_date ?? null;

                                // 2) Fallback: try reading from term (if dates are stored there)
                                if (!$startDateRaw) $startDateRaw = optional(optional($classOffering->curriculumTermSubject)->term)->start_date;
                                if (!$endDateRaw)   $endDateRaw   = optional(optional($classOffering->curriculumTermSubject)->term)->end_date;

                                // Format dates for display (handles null)
                                $startDate = $startDateRaw ? \Carbon\Carbon::parse($startDateRaw)->format('M d, Y') : '—';
                                $endDate   = $endDateRaw   ? \Carbon\Carbon::parse($endDateRaw)->format('M d, Y') : '—';

                                $subjectCode = $subject->code ?? 'N/A';
                                $subjectName = $subject->name ?? 'Subject not linked';
                                $sectionText = $section->name ?? $section->code ?? 'N/A';

                                /**
                                 * ✅ IMPORTANT:
                                 * Some projects have meetings() relationship that filters "current" only.
                                 * If you add meetingsAll() relationship later, this Blade will automatically use it.
                                 */
                                $meetingsRaw = $classOffering->meetingsAll ?? $classOffering->meetings ?? [];
                                $meetings = collect($meetingsRaw);

                                // ✅ Use correct DB fields: day_of_week, time_start, time_end
                                $schedule = $meetings
                                    ->map(function ($m) use ($dayMap) {
                                        $rawDay = $m->day_of_week ?? null;

                                        $day = is_numeric($rawDay)
                                            ? ($dayMap[(int)$rawDay] ?? (string)$rawDay)
                                            : ($rawDay ?: '');

                                        $start = $m->time_start ?? null;
                                        $end   = $m->time_end ?? null;

                                        $time = ($start && $end) ? "{$start}-{$end}" : ($start ?: null);

                                        return trim(collect([$day, $time])->filter()->join(' '));
                                    })
                                    ->filter()
                                    ->join(', ');

                                $searchText = strtolower($subjectCode.' '.$subjectName.' '.$sectionText.' '.$schedule);
                            @endphp

                            <tr class="row-item hover:bg-slate-50/60" data-search="{{ $searchText }}">
                                {{-- Subject --}}
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-900">{{ $subjectCode }}</div>
                                    <div class="text-xs text-slate-500">{{ $subjectName }}</div>
                                </td>

                                {{-- Section --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700">
                                        {{ $sectionText }}
                                    </span>
                                </td>

                                {{-- Start Date --}}
                                <td class="px-6 py-4 whitespace-nowrap text-slate-700">
                                    {{ $startDate }}
                                </td>

                                {{-- End Date --}}
                                <td class="px-6 py-4 whitespace-nowrap text-slate-700">
                                    {{ $endDate }}
                                </td>

                                {{-- Action --}}
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <a href="{{ route('teacher.evaluations.show', $classOffering->id) }}"
                                       class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-900 hover:bg-slate-50">
                                        Evaluate
                                    </a>
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-slate-500">
                                    No classes found for you.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
function filterRows(query) {
    const q = (query || '').toLowerCase().trim();
    document.querySelectorAll('#classesBody .row-item').forEach(row => {
        row.style.display = (row.dataset.search || '').includes(q) ? '' : 'none';
    });
}
</script>
@endpush
@endsection
