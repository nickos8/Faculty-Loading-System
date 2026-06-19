@extends('layouts.app')

@section('content')
@php
    $openTermModal = session('open_term_modal');
    $flashTermId = session('flash_term_id');
    $flashCtsId  = session('flash_cts_id');

    $failedSubjectId = old('subject_id');
    $failedSubject = null;
    if ($failedSubjectId && isset($choices)) {
        $failedSubject = $choices->firstWhere('id', (int)$failedSubjectId);
    }

    $failedTerm = null;
    if ($openTermModal && isset($curriculum) && $curriculum->relationLoaded('terms')) {
        $failedTerm = $curriculum->terms->firstWhere('id', (int)$openTermModal);
    }
    $failedTermLabel = $failedTerm ? "Year {$failedTerm->year_level}, Term {$failedTerm->term_no}" : null;
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- HEADER --}}
    <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
        <div class="p-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">
                    Curriculum: {{ $curriculum->code }}
                </h1>
                <p class="text-sm text-slate-600">
                    Program:
                    <span class="font-medium text-slate-900">{{ $curriculum->program->program_name ?? '—' }}</span>
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('curricula.index') }}"
                   class="px-3 py-2 text-xs font-medium rounded-xl bg-white border border-slate-200 text-slate-900 hover:bg-slate-50">
                    Back to Curricula
                </a>
            </div>
        </div>
    </div>

    {{-- YEARS --}}
    @foreach($grid as $yearLevel => $terms)
        <div class="space-y-4">
            <div class="grid lg:grid-cols-2 gap-4">

                @foreach($terms->sortBy('term_no') as $term)
                    @php
                        $thisTermIndex = $termOrder[$term->id] ?? 999999;

                        $completedBefore = collect($subjectPlacedAt)
                            ->filter(fn($tIndex) => $tIndex < $thisTermIndex)
                            ->keys()
                            ->map(fn($id) => (int)$id)
                            ->values()
                            ->all();

                        // term-scoped toggles
                        $shouldShowFlash = ((int)$flashTermId === (int)$term->id);

                        // Open Add Subject modal if controller says so
                        $autoOpenSubjectModal = ((int)$openTermModal === (int)$term->id);

                        // Open Date editor if controller says so
                        $autoOpenDatesEditor = ((int)session('open_dates_editor') === (int)$term->id) && $shouldShowFlash;

                        // named bags
                        $termDatesBag     = $errors->getBag('termDates');
                        $addSubjectBag    = $errors->getBag('addSubject');
                        $removeSubjectBag = $errors->getBag('removeSubject');

                        // subject picker options
                        $pickerOptions = $choices->map(function($opt) use ($completedBefore) {
                            $preIds = $opt->prerequisites->pluck('id')->map(fn($x)=>(int)$x)->all();
                            $missing = collect($preIds)->diff($completedBefore)->values()->all();

                            $missingCodes = collect($opt->prerequisites)
                                ->whereIn('id', $missing)
                                ->pluck('code')
                                ->values()
                                ->all();

                            $blocked = count($missing) > 0;

                            return [
                                'id' => $opt->id,
                                'code' => $opt->code,
                                'name' => $opt->name,
                                'blocked' => $blocked,
                                'blocked_reason' => $blocked ? ('Missing prerequisite(s): ' . implode(', ', $missingCodes)) : null,
                                'prereq_codes' => $opt->prerequisites->pluck('code')->values()->all(),
                            ];
                        })->values();

                        $subjects = $term->subjects ?? collect();
                    @endphp

                    <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden"
                         x-data="{
                            openSubject: {{ $autoOpenSubjectModal ? 'true' : 'false' }},
                            editingDates: {{ $autoOpenDatesEditor ? 'true' : 'false' }},
                         }"
                         @keydown.escape.window="openSubject=false">

                        {{-- TERM HEADER --}}
                        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/70">

                            {{-- ✅ term-scoped success: add subject --}}
                            @if($shouldShowFlash && session()->has('success_addSubject'))
                                @php($m = session('success_addSubject'))
                                <div class="mb-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 shadow-sm">
                                    <div class="font-semibold">{{ is_array($m) ? ($m['title'] ?? 'Success') : 'Success' }}</div>

                                    @if(is_array($m))
                                        <div class="text-sm mt-1">
                                            Subject Added:
                                            <span class="font-semibold">{{ data_get($m, 'subject.code', '—') }}</span>
                                            — {{ data_get($m, 'subject.name', '—') }}
                                        </div>
                                    @else
                                        <div class="text-sm mt-1">{{ $m }}</div>
                                    @endif
                                </div>
                            @endif

                            {{-- -  subject edit success --}}
                            @if($shouldShowFlash && session()->has('success_editSubject'))
                                @php($m = session('success_editSubject'))
                                <div class="mb-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 shadow-sm">
                                    <div class="font-semibold">Update Success</div>
                                    <div class="text-sm mt-1">

                                        Subject  : <span class="font-semibold">{{ data_get($m, 'subject.code', '—') }}</span>
                                         {{ data_get($m, 'subject.name', '—') }}
                                         <br>Units : {{ data_get($m, 'unit') }}
                                         <br>Type  : {{ data_get($m, 'type') }}
                                    </div>
                                </div>
                            @endif


                            {{-- ✅ term-scoped success: remove subject --}}
                            @if($shouldShowFlash && session()->has('success_removeSubject'))
                                @php($m = session('success_removeSubject'))
                                <div class="mb-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 shadow-sm">
                                    <div class="font-semibold">{{ is_array($m) ? ($m['title'] ?? 'Success') : 'Success' }}</div>

                                    @if(is_array($m))
                                        <div class="text-sm mt-1">
                                            Subject Removed:
                                            <span class="font-semibold">{{ data_get($m, 'subject.code', '—') }}</span>
                                            — {{ data_get($m, 'subject.name', '—') }}
                                        </div>
                                    @else
                                        <div class="text-sm mt-1">{{ $m }}</div>
                                    @endif
                                </div>
                            @endif

                            <div class="flex flex-col gap-3">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">

                                    {{-- LEFT: Year/Term + dates under it --}}
                                    <div class="min-w-0">
                                        <h3 class="text-base sm:text-lg font-semibold tracking-tight text-slate-900">
                                            YEAR {{ $term->year_level }}
                                            <span class="text-slate-300 mx-2">•</span>
                                            TERM {{ $term->term_no }}
                                        </h3>

                                        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-600">
                                            <span class="font-medium text-slate-900">
                                                {{ $term->start_date ? \Illuminate\Support\Carbon::parse($term->start_date)->format('M d, Y') : '' }}
                                                <span class="text-slate-400 mx-1">-</span>
                                                {{ $term->end_date ? \Illuminate\Support\Carbon::parse($term->end_date)->format('M d, Y') : '' }}
                                            </span>

                                            @if(empty($term->start_date) || empty($term->end_date))
                                                <span >

                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- RIGHT: Actions --}}
                                    <div class="flex flex-wrap items-center gap-2 lg:justify-end">

                                        {{-- Edit dates --}}
                                        <button type="button"
                                                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-900 hover:bg-slate-50"
                                                @click="editingDates = !editingDates">
                                            <span x-text="editingDates ? 'Close date editor' : 'Edit term dates'"></span>
                                        </button>

                                        {{-- Add subject --}}
                                        <button type="button"
                                                class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-800"
                                                @click="openSubject=true">
                                            Add Subject
                                        </button>
                                    </div>

                                </div>

                                {{-- DATE EDITOR PANEL (collapsible) --}}
                                <div x-cloak
                                     x-show="editingDates"
                                     x-transition
                                     class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">

                                    {{-- ✅ term-scoped success/errors for date editor --}}
                                    @if($shouldShowFlash && session()->has('success_termDates'))
                                        @php($m = session('success_termDates'))
                                        <div class="mb-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 shadow-sm">
                                            <div class="font-semibold">{{ is_array($m) ? ($m['title'] ?? 'Success') : 'Success' }}</div>
                                            <div class="text-sm mt-1">
                                                @if(is_array($m))
                                                    {{ data_get($m, 'message', 'Term dates updated.') }}
                                                @else
                                                    {{ $m }}
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    @if($shouldShowFlash && $termDatesBag->any())
                                        <div class="mb-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-800 shadow-sm">
                                            <div class="font-semibold">Unable to save term dates</div>
                                            <ul class="mt-2 list-disc list-inside text-sm space-y-1">
                                                @foreach($termDatesBag->all() as $e)
                                                    <li class="whitespace-pre-line">{{ $e }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="text-xs font-semibold text-slate-900">Set term date range</div>
                                            <div class="mt-1 text-[11px] text-slate-500">
                                                This range will apply to all offerings under this term.
                                            </div>
                                        </div>
                                    </div>

                                    <form method="POST"
                                          action="{{ route('terms.update_dates', $term->id) }}"
                                          class="mt-3 grid gap-3 sm:grid-cols-3 sm:items-end">
                                        @csrf
                                        @method('PUT')

                                        <div>
                                            <label class="block text-[11px] text-slate-500 mb-1">Start date</label>
                                            <input type="date"
                                                   name="start_date"
                                                   value="{{ optional($term->start_date)->format('Y-m-d') }}"
                                                   class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm
                                                          focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-300"
                                                   required>
                                        </div>

                                        <div>
                                            <label class="block text-[11px] text-slate-500 mb-1">End date</label>
                                            <input type="date"
                                                   name="end_date"
                                                   value="{{ optional($term->end_date)->format('Y-m-d') }}"
                                                   class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm
                                                          focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-300"
                                                   required>
                                        </div>

                                        <div class="flex gap-2 sm:justify-end">
                                            <button type="submit"
                                                    class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-800">
                                                Save dates
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                {{-- /DATE EDITOR --}}

                            </div>
                        </div>
                        {{-- /TERM HEADER --}}

                        {{-- TERM BODY --}}
                        <div class="p-6 space-y-3">
                            @if($subjects->isEmpty())
                                <div class="rounded-xl border border-dashed border-slate-200 p-6 text-center">
                                    <div class="text-sm font-medium text-slate-900">No subjects yet</div>

                                </div>
                            @else
                                @foreach($subjects as $cts)
                                    @php($s = $cts->subject)

                                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <div class="font-semibold text-slate-900 truncate">
                                                        {{ $s->name ?? '—' }}
                                                    </div>
                                                </div>

                                                <div class="text-xs text-slate-600 truncate mt-0.5">
                                                    <span class="inline-flex gap-2">
                                    <span><span class="font-semibold">Units:</span> {{ $cts->unit }}</span>

                                    @if($cts->type)
                                        <span><span class="font-semibold">Type:</span> {{ ($cts->type) }}</span>
                                    @endif
                                    </span>


                                                </div>

                                                {{-- row-scoped remove error --}}
                                                @if($shouldShowFlash && (int)$flashCtsId === (int)$cts->id && $removeSubjectBag->any())
                                                    @php($rmInfo = session('failed_removeSubject'))
                                                    <div class="mt-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-800">
                                                        @if(is_array($rmInfo) && (int)data_get($rmInfo, 'cts_id') === (int)$cts->id)
                                                            <div class="font-semibold">
                                                                Unable to remove
                                                                <span class="font-bold">{{ data_get($rmInfo, 'subject.code') }}</span>
                                                                — {{ data_get($rmInfo, 'subject.name') }}
                                                            </div>
                                                            <div class="mt-1 whitespace-pre-line opacity-90">
                                                                {{ $removeSubjectBag->first('remove') }}
                                                            </div>
                                                        @else
                                                            <div class="whitespace-pre-line">{{ $removeSubjectBag->first('remove') }}</div>
                                                        @endif
                                                    </div>
                                                @endif

                                                @php($pres = $s?->prerequisites ?? collect())
                                                <div class="mt-2 flex flex-wrap gap-1">
                                                    @foreach($pres as $p)
                                                        <span class="text-xs px-2 py-1 rounded-full bg-slate-50 text-slate-700 border border-slate-200">
                                                            prerequisite <span class="mx-2"></span>{{ $p->code }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>

                                              <div class="flex items-center gap-2">
                                            <a href="{{ route('terms.subjects.edit', [$term->id, $cts->id]) }}"
                                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-900 hover:bg-slate-50">
                                                Edit
                                            </a>

                                            <form action="{{ route('terms.subjects.destroy', [$term->id, $cts->id]) }}"
                                                method="POST"
                                                onsubmit="return confirm('Remove this subject from Term {{ $term->term_no }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-900 hover:bg-slate-50">
                                                    Remove
                                                </button>
                                            </form>
                                        </div>

                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                        {{-- /TERM BODY --}}

                        {{-- ADD SUBJECT MODAL --}}
                        <div x-show="openSubject" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                            <div class="absolute inset-0 bg-black/40" @click="openSubject=false"></div>

                            <div class="relative w-full max-w-4xl rounded-2xl border border-slate-200 bg-white shadow-xl overflow-hidden"
                                 style="max-height:82vh;"
                                 x-data="subjectPicker({
                                    options: @js($pickerOptions),
                                    selectedId: @js(old('subject_id')),
                                 })"
                                 @keydown.escape.window="openSubject=false">

                                <div class="px-5 py-4 border-b border-slate-100 bg-white flex items-start justify-between gap-3">
                                    <div>
                                        <div class="text-base font-semibold text-slate-900">Add Subject</div>
                                        <div class="text-xs text-slate-500">Term {{ $term->term_no }} • Year {{ $term->year_level }}</div>
                                    </div>
                                    <button type="button"
                                            class="text-slate-500 hover:text-slate-900"
                                            @click="openSubject=false"
                                            aria-label="Close">✕</button>
                                </div>

                                <div class="px-5 py-4 overflow-auto" style="max-height: calc(82vh - 116px);">

                                    {{-- modal-scoped error --}}
                                    @if($shouldShowFlash && $addSubjectBag->any())
                                        <div class="mb-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-800 shadow-sm">
                                            <div class="font-semibold">
                                                Unable to add subject
                                                @if($failedSubject)
                                                    : <span class="font-bold">{{ $failedSubject->code }}</span> — {{ $failedSubject->name }}
                                                @endif
                                                @if($failedTermLabel)
                                                    <span class="font-normal">({{ $failedTermLabel }})</span>
                                                @endif
                                            </div>
                                            <div class="text-sm mt-1 opacity-90">
                                                Please review the details below and correct the highlighted fields.
                                            </div>
                                        </div>
                                    @endif

                                    <form action="{{ route('terms.subjects.store', $term->id) }}" method="POST" class="space-y-4">
                                        @csrf
                                        <input type="hidden" name="subject_id" :value="selectedId ?? ''">

                                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600">
                                            A subject is <span class="font-semibold text-slate-900">blocked</span> if its prerequisites are not placed in earlier terms.
                                        </div>

                                        <div class="grid lg:grid-cols-2 gap-4">
                                            {{-- Left list --}}
                                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                                <label class="block text-xs font-medium text-slate-500 mb-2">Search subjects</label>

                                                <input x-model="q"
                                                       type="text"
                                                       placeholder="Search subject code or name…"
                                                       class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm
                                                              focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">

                                                <div class="mt-3 max-h-[18rem] overflow-auto space-y-2 pr-1">
                                                    <template x-for="item in filtered()" :key="item.id">
                                                        <button type="button"
                                                                class="w-full text-left rounded-xl border px-3 py-2.5 flex items-start justify-between gap-3"
                                                                :class="item.blocked
                                                                    ? 'border-amber-200 bg-amber-50/50 opacity-70 cursor-not-allowed'
                                                                    : (isSelected(item.id)
                                                                        ? 'border-slate-900 bg-slate-50'
                                                                        : 'border-slate-200 bg-white hover:bg-slate-50')"
                                                                :disabled="item.blocked"
                                                                @click="select(item.id)">

                                                            <div class="min-w-0">
                                                                <div class="flex items-center gap-2">
                                                                    <div class="text-sm font-semibold text-slate-900" x-text="item.code"></div>
                                                                    <template x-if="item.blocked">
                                                                        <span class="text-[11px] px-2 py-0.5 rounded-full border bg-amber-100 text-amber-800 border-amber-200">
                                                                            Blocked
                                                                        </span>
                                                                    </template>
                                                                </div>

                                                                <div class="text-xs text-slate-600 mt-0.5" x-text="item.name"></div>

                                                                <template x-if="item.blocked">
                                                                    <div class="mt-1 text-xs text-amber-800">
                                                                        <span class="font-semibold">Missing:</span>
                                                                        <span x-text="item.blocked_reason"></span>
                                                                    </div>
                                                                </template>

                                                                <template x-if="(!item.blocked && item.prereq_codes && item.prereq_codes.length)">
                                                                    <div class="mt-1 text-xs text-slate-500">
                                                                        Prereq: <span x-text="item.prereq_codes.join(', ')"></span>
                                                                    </div>
                                                                </template>
                                                            </div>

                                                            <div class="text-xs px-2 py-1 rounded-full border shrink-0"
                                                                 :class="isSelected(item.id)
                                                                    ? 'bg-slate-900 text-white border-slate-900'
                                                                    : 'bg-white text-slate-700 border-slate-200'">
                                                                <span x-text="isSelected(item.id) ? 'Selected' : 'Select'"></span>
                                                            </div>
                                                        </button>
                                                    </template>

                                                    <div class="text-xs text-slate-500 italic" x-show="filtered().length === 0">
                                                        No subjects match your search.
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Right panel --}}
                                            <div class="rounded-2xl border border-slate-200 bg-white p-4 space-y-3">
                                                <div class="flex items-center justify-between">
                                                    <div class="text-sm font-semibold text-slate-900">Selected</div>
                                                    <button type="button"
                                                            class="text-xs text-slate-600 hover:text-slate-900"
                                                            x-show="selectedId"
                                                            @click="clear()">Clear</button>
                                                </div>

                                                <div class="space-y-2">
                                                    <template x-if="selectedId">
                                                        <div class="rounded-xl border border-slate-200 bg-white px-3 py-3">
                                                            <div class="text-sm font-semibold text-slate-900" x-text="selectedItem().code"></div>
                                                            <div class="text-xs text-slate-600 mt-0.5" x-text="selectedItem().name"></div>

                                                            <template x-if="selectedItem().prereq_codes && selectedItem().prereq_codes.length">
                                                                <div class="mt-2 text-xs text-slate-500">
                                                                    Prerequisites: <span x-text="selectedItem().prereq_codes.join(', ')"></span>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </template>

                                                    <div class="text-xs text-slate-400 italic" x-show="!selectedId">None selected.</div>

                                                    @error('subject_id', 'addSubject')
                                                        <div class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-800 whitespace-pre-line">
                                                            <span class="font-semibold">Cannot add subject:</span> {{ $message }}
                                                        </div>
                                                    @enderror
                                                </div>

                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-1">
                                                    <div>
                                                        <label class="block text-xs font-medium text-slate-500 mb-1">Units</label>
                                                        <input type="number"
                                                               name="unit"
                                                               step="0.5"
                                                               min="0.5"
                                                               max="10"
                                                               value="{{ old('unit') }}"
                                                               placeholder="e.g., 3"
                                                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm
                                                                      focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                                                        @error('unit', 'addSubject')
                                                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                                        @enderror
                                                    </div>

                                                    <div>
                                                        <label class="block text-xs font-medium text-slate-500 mb-1">Type</label>
                                                        <select name="type"
                                                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm
                                                                       focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                                                                required>
                                                            <option value=""> Select Type </option>
                                                            @foreach(['major','minor','elective','general','thesis','internship'] as $t)
                                                                <option value="{{ $t }}" @selected(old('type') === $t)>{{ ucfirst($t) }}</option>
                                                            @endforeach
                                                        </select>
                                                        @error('type', 'addSubject')
                                                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                                            <button type="button"
                                                    class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-medium text-slate-900 hover:bg-slate-50"
                                                    @click="openSubject=false">
                                                Cancel
                                            </button>

                                            <button type="submit"
                                                    class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800 disabled:opacity-60"
                                                    :disabled="!selectedId">
                                                Add subject
                                            </button>
                                        </div>
                                    </form>

                                </div>
                            </div>
                        </div>
                        {{-- /ADD SUBJECT MODAL --}}

                    </div>
                @endforeach

            </div>
        </div>
    @endforeach
</div>
@endsection

@push('scripts')
<script>
function subjectPicker({ options, selectedId }) {
    return {
        options: Array.isArray(options) ? options : [],
        selectedId: (selectedId !== null && selectedId !== undefined && selectedId !== '') ? Number(selectedId) : null,
        q: '',
        filtered() {
            const q = this.q.trim().toLowerCase();
            if (!q) return this.options.slice(0, 160);
            return this.options.filter(o =>
                (o.code || '').toLowerCase().includes(q) ||
                (o.name || '').toLowerCase().includes(q)
            ).slice(0, 160);
        },
        isSelected(id) { return this.selectedId === Number(id); },
        select(id) { this.selectedId = Number(id); },
        clear() { this.selectedId = null; },
        selectedItem() {
            return this.options.find(o => o.id === this.selectedId) || { code: '—', name: 'Unknown', prereq_codes: [] };
        },
    }
}
</script>
@endpush
