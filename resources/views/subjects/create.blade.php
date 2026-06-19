@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6 space-y-6">

    {{-- HEADER (dashboard style) --}}
    <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
        <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">Create Subject</h1>
                <p class="text-sm text-slate-600">Add a new subject and optionally configure prerequisites.</p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('subjects.index') }}"
                   class="px-3 py-2 text-xs font-medium rounded-xl bg-white border border-slate-200 text-slate-900 hover:bg-slate-50">
                    Back to Subjects
                </a>
            </div>
        </div>
    </div>

    {{-- GLOBAL ERRORS (dashboard style) --}}
    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-800 shadow-sm">
            <div class="font-semibold mb-1">Please fix the following:</div>
            <ul class="list-disc list-inside text-sm space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- FORM CARD --}}
    <div class="rounded-2xl border border-slate-200/70 bg-slate-50 shadow-sm overflow-hidden">
        <form method="POST" action="{{ route('subjects.store') }}" class="p-6 space-y-6">
            @csrf

            {{-- TIP (dashboard toned-down) --}}
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                <span class="font-semibold text-slate-900">Tip:</span>
                Prerequisites are subjects a student must complete <span class="font-semibold">before</span> taking this subject.
            </div>

            {{-- SUBJECT DETAILS --}}
            <div>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Subject Details</h2>
                        <p class="text-xs text-slate-500 mt-1">Use a consistent code format (e.g., CS101) to make searching easier.</p>
                    </div>
                    <div class="text-xs text-slate-500">
                        Fields marked <span class="text-rose-600 font-medium">*</span> are required
                    </div>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    {{-- Code --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">
                            Subject Code <span class="text-rose-600">*</span>
                        </label>
                        <input
                            type="text"
                            name="code"
                            value="{{ old('code') }}"
                            placeholder="e.g., CS101"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                                   focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                        >
                        @error('code')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Name --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">
                            Subject Name <span class="text-rose-600">*</span>
                        </label>
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            placeholder="e.g., Introduction to Programming"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                                   focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                        >
                        @error('name')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- PREREQUISITES PICKER --}}
            <div class="pt-2 border-t border-slate-100"
                 x-data="prereqPicker({
                    lookupUrl: @js(route('subjects.lookup')),
                    excludeId: null,
                    selected: @js($selectedPrereqIds ?? []),
                    selectedItems: @js(($selectedPrereqItems ?? collect())->map(fn($s)=>[
                        'id'=>$s->id,
                        'code'=>$s->code,
                        'name'=>$s->name,
                    ])->values()),
                 })"
                 x-init="init()">

                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Prerequisites</h3>
                        <p class="mt-1 text-xs text-slate-500">
                            Search and select prerequisites. You’ll see selections on the right before saving.
                        </p>
                    </div>
                    <span class="text-xs text-slate-500">Optional</span>
                </div>

                {{-- Hidden input list for submission --}}
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="prerequisite_ids[]" :value="id">
                </template>

                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                    {{-- Left: available --}}
                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <label class="block text-xs font-medium text-slate-500 mb-2">Search subjects</label>
                        <input x-model="q"
                               @input="debouncedSearch()"
                               type="text"
                               placeholder="Search subject code or name…"
                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm
                                      focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">

                        <div class="mt-3 max-h-72 overflow-auto space-y-2 pr-1">
                            <div class="text-xs text-slate-500 italic" x-show="loading">Loading…</div>

                            <template x-for="item in results" :key="item.id">
                                <button type="button"
                                        class="w-full text-left rounded-xl border px-3 py-2 hover:bg-slate-50 flex items-start justify-between gap-3"
                                        :class="isSelected(item.id) ? 'border-slate-300 bg-slate-50' : 'border-slate-200 bg-white'"
                                        @click="toggle(item.id)">
                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold text-slate-900" x-text="item.code"></div>
                                        <div class="text-xs text-slate-600 truncate" x-text="item.name"></div>
                                    </div>

                                    <div class="text-xs px-2 py-1 rounded-full border shrink-0"
                                         :class="isSelected(item.id) ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 border-slate-200'">
                                        <span x-text="isSelected(item.id) ? 'Selected' : 'Add'"></span>
                                    </div>
                                </button>
                            </template>

                            <div class="text-xs text-slate-500 italic" x-show="!loading && results.length === 0">
                                No subjects match your search.
                            </div>
                        </div>
                    </div>

                    {{-- Right: selected --}}
                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <div class="flex items-center justify-between">
                            <div class="text-sm font-semibold text-slate-900">Selected prerequisites</div>

                            <button type="button"
                                    class="text-xs text-slate-600 hover:text-slate-900"
                                    x-show="selected.length"
                                    @click="clearAll()">
                                Clear all
                            </button>
                        </div>

                        <div class="mt-3 space-y-2">
                            <template x-for="id in selected" :key="id">
                                <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2">
                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold text-slate-900 truncate" x-text="byId(id).code"></div>
                                        <div class="text-xs text-slate-600 truncate" x-text="byId(id).name"></div>
                                    </div>

                                    <button type="button"
                                            class="text-xs rounded-lg border border-rose-200 bg-rose-50 text-rose-700 px-2 py-1 hover:bg-rose-100"
                                            @click="remove(id)">
                                        Remove
                                    </button>
                                </div>
                            </template>

                            <div class="text-xs text-slate-400 italic" x-show="selected.length === 0">
                                None selected.
                            </div>
                        </div>

                        {{-- Validation for prerequisites --}}
                        @error('prerequisite_ids')
                            <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                        @error('prerequisite_ids.*')
                            <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- ACTIONS (dashboard style) --}}
            <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <a href="{{ route('subjects.index') }}"
                   class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-medium text-slate-900 hover:bg-slate-50">
                    Cancel
                </a>

                <button type="submit"
                        class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800">
                    Save Subject
                </button>
            </div>
        </form>
    </div>
    {{-- INFO BAR --}}
<div class="rounded-2xl border border-blue-100 bg-blue-50/50 p-4 shadow-sm">
    <div class="flex gap-3">
        <div class="flex-shrink-0">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div class="flex-1">
            <div class="text-sm font-medium text-blue-900">Before You Create a Subject</div>
            <div class="text-xs text-blue-700 mt-1 space-y-1">
                <p>Make sure the <strong>Subject Code</strong> is unique (e.g., CS101).</p>
                <p>Provide a clear and descriptive <strong>Subject Name</strong>.</p>
                <p>You may optionally assign <strong>prerequisites</strong> to control enrollment order.</p>
                <p>All subjects created will be linked to your assigned program.</p>
            </div>
        </div>
    </div>
</div>

</div>

@endsection

@push('scripts')
<script>
function prereqPicker({ lookupUrl, excludeId = null, selected = [], selectedItems = [] }) {
    return {
        lookupUrl,
        excludeId,
        q: '',
        loading: false,
        results: [],
        selected: Array.isArray(selected) ? selected.map(Number) : [],
        // cache keeps info for selected IDs even if not in current results
        cache: Object.fromEntries((selectedItems || []).map(i => [Number(i.id), i])),
        _t: null,

        init() {
            this.search(); // load initial 30 results
        },

        debouncedSearch() {
            clearTimeout(this._t);
            this._t = setTimeout(() => this.search(), 250);
        },

        async search() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.q.trim()) params.set('q', this.q.trim());
                if (this.excludeId) params.set('exclude', this.excludeId);

                const res = await fetch(`${this.lookupUrl}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' }
                });

                const data = await res.json();
                this.results = Array.isArray(data) ? data : [];

                // update cache so selected panel can render code/name
                this.results.forEach(it => {
                    this.cache[Number(it.id)] = it;
                });
            } catch (e) {
                this.results = [];
            } finally {
                this.loading = false;
            }
        },

        byId(id) {
            return this.cache[Number(id)] || { code: '—', name: 'Unknown' };
        },

        isSelected(id) {
            return this.selected.includes(Number(id));
        },

        toggle(id) {
            id = Number(id);
            if (this.isSelected(id)) this.remove(id);
            else this.selected.push(id);
        },

        remove(id) {
            id = Number(id);
            this.selected = this.selected.filter(x => x !== id);
        },

        clearAll() {
            this.selected = [];
        },
    }
}
</script>
@endpush
