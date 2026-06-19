@extends('layouts.app')

@section('content')
@php
    // Collect ALL custom rows once for delete forms + modals (avoids nesting issues)
    $customRows = collect($groupedUnified ?? [])
        ->flatMap(fn($items) => $items)
        ->filter(fn($item) => ($item->row_type ?? null) === 'custom')
        ->map(fn($item) => $item->model)
        ->values();

    $statusLabels = [
        'not_taken' => 'Not taken',
        'enrolled'  => 'Enrolled',
        'passed'    => 'Passed',
        'failed'    => 'Failed',
        'credited'  => 'Credited',
    ];

    $statusClass = fn($st) => match($st) {
        'passed'   => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'enrolled' => 'border-blue-200 bg-blue-50 text-blue-700',
        'failed'   => 'border-rose-200 bg-rose-50 text-rose-700',
        'credited' => 'border-amber-200 bg-amber-50 text-amber-700',
        default    => 'border-slate-200 bg-slate-50 text-slate-600',
    };
@endphp

<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    {{-- HEADER (dashboard style) --}}
    <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
        <div class="p-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">
                    Manage Curriculum
                </h1>
                <p class="text-sm text-slate-600">
                    View curriculum subjects, including transfer credits and extra loads.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('program-admin.students.index') }}"
                   class="px-3 py-2 text-xs font-medium rounded-xl bg-white border border-slate-200 text-slate-900 hover:bg-slate-50">
                    Back to Students List
                </a>
            </div>
        </div>

        {{-- Student meta pills --}}
        <div class="px-6 pb-6">
            <div class="flex flex-wrap gap-2 text-xs">
                <span class="text-slate-700">
                    <span class="text-slate-400">Student :</span>
                    <span class="font-semibold text-slate-900">{{ $student->first_name }} {{ $student->last_name }}</span>
                </span>
            </div>
        </div>
    </div>

    {{-- FLASH / ERRORS --}}
    @if (session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 shadow-sm">
            <div class="font-semibold">Success</div>
            <div class="mt-1 text-sm">{{ session('success') }}</div>
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-800 shadow-sm">
            <div class="font-semibold mb-1">Please fix the following:</div>
            <ul class="list-disc list-inside text-sm space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- CURRICULUM BY TERM --}}
    @forelse ($groupedUnified as $termLabel => $items)
        @php
            $totalUnits = collect($items)->sum(function ($item) {
                $isCustom = $item->row_type === 'custom';
                $row = $item->model;

                return $isCustom
                    ? ($row->external_units ?? 0)
                    : (optional($row->curriculumTermSubject)->unit ?? 0);
            });
        @endphp

        <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">

            {{-- TERM HEADER --}}
            <div class="px-6 py-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-sm font-semibold text-slate-900">{{ $termLabel }}</div>
                <div class="text-xs text-slate-500">
                    Total units: <span class="font-medium text-slate-700">{{ $totalUnits }}</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-xs sm:text-sm table-fixed">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold text-slate-600">
                            <th class="px-6 py-3 w-28">Code</th>
                            <th class="px-6 py-3">Subject</th>
                            <th class="px-6 py-3 text-center w-20">Units</th>
                            <th class="px-6 py-3 text-center w-24">Type</th>
                            <th class="px-6 py-3 text-center w-28">Status</th>
                            <th class="px-6 py-3 text-center w-24">Source</th>
                            <th class="px-6 py-3 text-right w-44">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                    @foreach ($items as $item)
                        @php
                            $isCustom = $item->row_type === 'custom';
                            $row = $item->model;

                            $subject = $isCustom
                                ? $row->subject
                                : optional($row->curriculumTermSubject)->subject;

                            $code = $subject->code ?? '—';
                            $name = $subject->name ?? 'Unknown subject';

                            $units = $isCustom
                                ? ($row->external_units ?? null)
                                : (optional($row->curriculumTermSubject)->unit ?? null);

                            $type = $isCustom
                                ? ($row->subject_type ? strtoupper($row->subject_type) : null)
                                : (optional($row->curriculumTermSubject)->type
                                    ? strtoupper(optional($row->curriculumTermSubject)->type)
                                    : null);

                            $locked = in_array($row->status, ['enrolled', 'passed', 'failed'], true);

                            $st = $row->status ?? 'not_taken';
                            $stLabel = $statusLabels[$st] ?? ucfirst(str_replace('_', ' ', $st));
                        @endphp

                        <tr class="hover:bg-slate-50/60">
                            {{-- CODE --}}
                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-900">{{ $code }}</div>
                            </td>

                            {{-- SUBJECT --}}
                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-900 break-words leading-snug">
                                    {{ $name }}
                                </div>
                            </td>

                            {{-- UNITS --}}
                            <td class="px-6 py-4 text-center whitespace-nowrap text-slate-700">
                                {{ $units ?? '—' }}
                            </td>

                            {{-- TYPE --}}
                            <td class="px-6 py-4 text-center whitespace-nowrap text-slate-700">
                                {{ $type ?? '—' }}
                            </td>

                            {{-- STATUS --}}
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-full text-xs font-medium border {{ $statusClass($st) }}">
                                    {{ $stLabel }}
                                </span>
                            </td>

                            {{-- SOURCE --}}
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                @if($isCustom)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full border border-indigo-200 bg-indigo-50 text-xs text-indigo-700">
                                        Custom
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full border border-slate-200 bg-slate-50 text-xs text-slate-700">
                                        Official
                                    </span>
                                @endif
                            </td>

                            {{-- ACTION --}}
                            <td class="px-6 py-4 text-right align-top">
                                @if($isCustom)
                                    <div class="inline-flex items-center gap-2 justify-end">
                                        <button
                                            type="button"
                                            onclick="openEditModal({{ $row->id }})"
                                            class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60 disabled:cursor-not-allowed"
                                            @disabled($locked)
                                        >
                                            Edit
                                        </button>

                                        <button
                                            type="submit"
                                            form="deleteCustomForm-{{ $row->id }}"
                                            onclick="return confirm('Remove this custom subject?');"
                                            class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-900 hover:bg-slate-50 disabled:opacity-60 disabled:cursor-not-allowed"
                                            @disabled($locked)
                                        >
                                            Remove
                                        </button>
                                    </div>

                                    <div class="mt-2 text-[11px] leading-4 min-h-[16px]">
                                        @if($locked)
                                            <span class="text-rose-600">Cannot edit/remove when {{ $row->status }}.</span>
                                        @else
                                            <span class="text-transparent select-none">placeholder</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-slate-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center shadow-sm">
            <div class="text-sm font-semibold text-slate-900">No curriculum subjects found</div>
            <div class="mt-1 text-xs text-slate-500">
                This student has no curriculum rows yet.
            </div>
        </div>
    @endforelse

    {{-- DELETE FORMS --}}
    @foreach ($customRows as $row)
        <form id="deleteCustomForm-{{ $row->id }}"
              method="POST"
              action="{{ route('program-admin.students.curriculum.custom.destroy', [$student->id, $row->id]) }}"
              class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endforeach

    {{-- ADD CUSTOM / EXTRA SUBJECT --}}
    <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
        <div class="px-6 py-4">
            <div class="text-sm font-semibold text-slate-900">Add Custom / Extra Subject</div>
            <div class="mt-1 text-xs text-slate-500">
                Use this for transfer credits, remedials, or extra loads not part of the official curriculum.
            </div>
        </div>

        <form method="POST"
              action="{{ route('program-admin.students.curriculum.custom.store', $student->id) }}"
              class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf

            {{-- Subject (SEARCH) --}}
            <div
                x-data="subjectPicker({
                    endpoint: '{{ route('program-admin.students.curriculum.subjects.search', $student->id) }}',
                    name: 'subject_id',
                    initialId: '{{ old('subject_id') }}',
                    initialLabel: '',
                    minChars: 2
                })"
                class="relative md:col-span-2"
            >
                <label class="block text-xs font-medium text-slate-500 mb-1">Subject</label>

                <input
                    type="text"
                    x-model="search"
                    @input.debounce.300ms="fetchResults()"
                    @focus="open = true"
                    placeholder="Type subject code or name..."
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                >

                <input type="hidden" :name="name" :value="selectedId" required>

                <div x-show="open" x-cloak
                     class="absolute z-50 mt-1 w-full rounded-xl border border-slate-200 bg-white shadow-lg overflow-hidden">
                    <template x-if="loading">
                        <div class="px-3 py-2 text-sm text-slate-500">Searching…</div>
                    </template>

                    <template x-if="!loading && results.length === 0 && (search || '').trim().length >= minChars">
                        <div class="px-3 py-2 text-sm text-slate-500">No matches.</div>
                    </template>

                    <template x-for="item in results" :key="item.id">
                        <button
                            type="button"
                            class="w-full text-left px-3 py-2 text-sm hover:bg-slate-50"
                            @click="selectItem(item)"
                        >
                            <span class="font-medium text-slate-900" x-text="item.code"></span>
                            <span class="text-slate-500" x-text="' — ' + item.name"></span>
                        </button>
                    </template>
                </div>

                <div class="mt-1 text-xs text-slate-500" x-show="selectedId">
                    Selected:
                    <span class="font-medium text-slate-700" x-text="selectedLabel"></span>
                    <button type="button" class="ml-2 underline" @click="clearSelection()">Change</button>
                </div>
            </div>

            {{-- Units --}}
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Units</label>
                <input type="number"
                       name="external_units"
                       step="0.5"
                       min="0.5"
                       value="{{ old('external_units') }}"
                       required
                       class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
            </div>

            {{-- Display Year --}}
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Year No. (display)</label>
                <input type="number"
                       name="display_year_level"
                       min="1"
                       max="10"
                       value="{{ old('display_year_level') }}"
                       class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
            </div>

            {{-- Display Term --}}
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Term No. (display)</label>
                <input type="number"
                       name="display_term_no"
                       min="1"
                       max="10"
                       value="{{ old('display_term_no') }}"
                       class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
            </div>

            {{-- Type --}}
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Type</label>
                <select name="subject_type"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                    <option value="">Select type</option>
                    @foreach (['major','minor','elective','general','thesis','internship'] as $opt)
                        <option value="{{ $opt }}" @selected(old('subject_type') == $opt)>{{ ucfirst($opt) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Status --}}
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
                <select name="status"
                        required
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                    @foreach (['not_taken','credited'] as $opt)
                        <option value="{{ $opt }}" @selected(old('status') == $opt)>
                            {{ ucfirst(str_replace('_', ' ', $opt)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2 flex justify-end pt-2">
                <button type="submit"
                        class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800">
                    Add Custom Subject
                </button>
            </div>
        </form>
    </div>

    {{-- EDIT MODALS --}}
    @foreach ($customRows as $row)
        @php
            $subj = $row->subject;
            $initialLabel = $subj ? ($subj->code . ' — ' . $subj->name) : '';
        @endphp

        <div id="editModal-{{ $row->id }}" class="fixed inset-0 hidden items-center justify-center bg-black/40 z-50 px-4">
            <div class="absolute inset-0" onclick="closeEditModal({{ $row->id }})"></div>

            <div class="relative w-full max-w-2xl rounded-2xl border border-slate-200 bg-white shadow-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Edit Custom Subject</h3>
                        <p class="mt-1 text-xs text-slate-500">Update the custom subject details for this student.</p>
                    </div>

                    <button type="button"
                            class="inline-flex items-center justify-center h-9 w-9 rounded-xl border border-slate-200 bg-white text-slate-500 hover:bg-slate-50"
                            onclick="closeEditModal({{ $row->id }})">
                        ✕
                    </button>
                </div>

                <form method="POST"
                      action="{{ route('program-admin.students.curriculum.custom.update', [$student->id, $row->id]) }}"
                      class="p-6 space-y-4">
                    @csrf
                    @method('PATCH')

                    {{-- Subject --}}
                    <div
                        x-data="subjectPicker({
                            endpoint: '{{ route('program-admin.students.curriculum.subjects.search', $student->id) }}',
                            name: 'subject_id',
                            initialId: '{{ $row->subject_id }}',
                            initialLabel: @js($initialLabel),
                            minChars: 2
                        })"
                        class="relative"
                    >
                        <label class="block text-xs font-medium text-slate-500 mb-1">Subject</label>

                        <input
                            type="text"
                            x-model="search"
                            @input.debounce.300ms="fetchResults()"
                            @focus="open = true"
                            placeholder="Type subject code or name..."
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                        >

                        <input type="hidden" :name="name" :value="selectedId" required>

                        <div x-show="open" x-cloak
                             class="absolute z-50 mt-1 w-full rounded-xl border border-slate-200 bg-white shadow-lg max-h-60 overflow-auto">
                            <template x-if="loading">
                                <div class="px-3 py-2 text-sm text-slate-500">Searching…</div>
                            </template>

                            <template x-if="!loading && results.length === 0 && (search || '').trim().length >= minChars">
                                <div class="px-3 py-2 text-sm text-slate-500">No matches.</div>
                            </template>

                            <template x-for="item in results" :key="item.id">
                                <button type="button"
                                        class="w-full text-left px-3 py-2 text-sm hover:bg-slate-50"
                                        @click="selectItem(item)">
                                    <span class="font-medium text-slate-900" x-text="item.code"></span>
                                    <span class="text-slate-500" x-text="' — ' + item.name"></span>
                                </button>
                            </template>
                        </div>

                        <div class="mt-1 text-xs text-slate-500" x-show="selectedId">
                            Selected:
                            <span class="font-medium text-slate-700" x-text="selectedLabel"></span>
                            <button type="button" class="ml-2 underline" @click="clearSelection()">Change</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Year Level</label>
                            <input type="number"
                                   name="display_year_level"
                                   min="1"
                                   max="10"
                                   value="{{ $row->display_year_level }}"
                                   class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Term No</label>
                            <input type="number"
                                   name="display_term_no"
                                   min="1"
                                   max="10"
                                   value="{{ $row->display_term_no }}"
                                   class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Units</label>
                            <input type="number"
                                   step="0.5"
                                   min="0.5"
                                   max="30"
                                   name="external_units"
                                   value="{{ $row->external_units }}"
                                   class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Type</label>
                            <select name="subject_type"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                                @php($types = ['major','minor','elective','general','thesis','internship'])
                                @foreach ($types as $t)
                                    <option value="{{ $t }}" @selected($row->subject_type === $t)>
                                        {{ ucfirst($t) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
                        <select name="status"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                            @foreach (['not_taken','credited'] as $st)
                                <option value="{{ $st }}" @selected($row->status === $st)>
                                    {{ ucwords(str_replace('_', ' ', $st)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button"
                                class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-medium text-slate-700 hover:bg-slate-50"
                                onclick="closeEditModal({{ $row->id }})">
                            Cancel
                        </button>

                        <button type="submit"
                                class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800">
                            Save changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

</div>

<script>
function openEditModal(id) {
    const el = document.getElementById('editModal-' + id);
    if (el) {
        el.classList.remove('hidden');
        el.classList.add('flex');
    }
}

function closeEditModal(id) {
    const el = document.getElementById('editModal-' + id);
    if (el) {
        el.classList.add('hidden');
        el.classList.remove('flex');
    }
}

// ESC closes all modals
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('[id^="editModal-"]').forEach((el) => {
            el.classList.add('hidden');
            el.classList.remove('flex');
        });
    }
});

/**
 * Subject search picker (Alpine component)
 * Requires Alpine.js loaded in your layout.
 */
function subjectPicker({ endpoint, name, initialId = '', initialLabel = '', minChars = 2 }) {
    return {
        endpoint,
        name,
        minChars,
        search: initialLabel || '',
        selectedId: initialId || '',
        selectedLabel: initialLabel || '',
        results: [],
        open: false,
        loading: false,

        async init() {
            if (this.selectedId && !this.selectedLabel) {
                try {
                    const r = await fetch(this.endpoint + '?id=' + encodeURIComponent(this.selectedId), {
                        headers: { 'Accept': 'application/json' }
                    });
                    const j = await r.json();
                    if (j.data && j.data[0]) {
                        this.selectedLabel = j.data[0].label;
                        this.search = this.selectedLabel;
                    }
                } catch (e) {}
            }

            document.addEventListener('click', (e) => {
                if (!this.$el.contains(e.target)) this.open = false;
            });
        },

        async fetchResults() {
            const q = (this.search || '').trim();

            if (q.length < this.minChars) {
                this.results = [];
                return;
            }

            this.loading = true;
            this.open = true;

            try {
                const url = this.endpoint + '?q=' + encodeURIComponent(q) + '&limit=20';
                const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const j = await r.json();
                this.results = j.data || [];
            } catch (e) {
                this.results = [];
            } finally {
                this.loading = false;
            }
        },

        selectItem(item) {
            this.selectedId = item.id;
            this.selectedLabel = item.label;
            this.search = item.label;
            this.open = false;
            this.results = [];
        },

        clearSelection() {
            this.selectedId = '';
            this.selectedLabel = '';
            this.search = '';
            this.results = [];
            this.open = true;
        }
    }
}
</script>
@endsection
