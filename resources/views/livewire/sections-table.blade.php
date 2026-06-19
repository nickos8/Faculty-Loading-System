<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    {{-- Flash messages --}}
    @if (session('status'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 shadow-sm">
            <div class="flex items-start gap-3">
                <div>
                    <div class="font-semibold">Success</div>
                    <div class="text-sm mt-1">{{ session('status') }}</div>
                </div>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-800 shadow-sm">
            <div class="font-semibold mb-1">Please fix the following:</div>
            <ul class="list-disc list-inside text-sm space-y-1">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Header --}}
    <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
        <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
                <h2 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">
                    Sections
                </h2>
                <p class="text-sm text-slate-600">
                    Create and manage sections, view students, and handle section lifecycle.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('sections.create') }}"
                   class="px-3 py-2 text-xs font-medium rounded-xl bg-slate-900 text-white hover:bg-slate-800">
                    Create New Section
                </a>
            </div>
        </div>
    </div>

    {{-- Filters + Search --}}
<div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm">
    <form method="GET" action="{{ route('sections.index') }}" class="p-4 sm:p-6 space-y-4">

        {{-- Top Row --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">

            {{-- Search --}}
            <div class="lg:col-span-7">
                <label for="q" class="block text-xs font-medium text-slate-600 mb-1">
                    Search Sections
                </label>
                <input
                    type="text"
                    name="q"
                    id="q"
                    value="{{ request('q') }}"
                    placeholder="Search by section name, year, term, or capacity"
                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm
                           focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                >
            </div>

            {{-- Per Page --}}
            <div class="lg:col-span-2">
                <label for="per_page" class="block text-xs font-medium text-slate-600 mb-1">
                    Show
                </label>
                <select
                    name="per_page"
                    id="per_page"
                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm
                           focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                >
                    @foreach([10, 15, 25, 50, 100] as $size)
                        <option value="{{ $size }}" {{ (int) request('per_page', 15) === $size ? 'selected' : '' }}>
                            {{ $size }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Apply --}}
            <div class="lg:col-span-1 flex items-end">
                <button
                    type="submit"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800"
                >
                    Apply
                </button>
            </div>

            {{-- Reset --}}
            <div class="lg:col-span-2 flex items-end">
                <a
                    href="{{ route('sections.index') }}"
                    class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-medium text-slate-700 hover:bg-slate-50"
                >
                    Reset
                </a>
            </div>
        </div>

        {{-- Status Tabs --}}
        <div class="flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4">
            <span class="text-xs font-medium text-slate-500 mr-1">Status:</span>

            <a href="{{ route('sections.index', array_merge(request()->except('page', 'status'), ['status' => 'active'])) }}"
               class="inline-flex items-center gap-1 px-4 py-2 rounded-xl text-xs font-semibold transition
                      {{ $status === 'active' ? 'bg-slate-900 text-white shadow-sm' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                <span>Active</span>
                <span class="{{ $status === 'active' ? 'text-white/80' : 'text-slate-500' }}">
                    ({{ $counts['active'] ?? 0 }})
                </span>
            </a>

            <a href="{{ route('sections.index', array_merge(request()->except('page', 'status'), ['status' => 'archived'])) }}"
               class="inline-flex items-center gap-1 px-4 py-2 rounded-xl text-xs font-semibold transition
                      {{ $status === 'archived' ? 'bg-slate-900 text-white shadow-sm' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                <span>Archived</span>
                <span class="{{ $status === 'archived' ? 'text-white/80' : 'text-slate-500' }}">
                    ({{ $counts['archived'] ?? 0 }})
                </span>
            </a>

            <a href="{{ route('sections.index', array_merge(request()->except('page', 'status'), ['status' => 'all'])) }}"
               class="inline-flex items-center gap-1 px-4 py-2 rounded-xl text-xs font-semibold transition
                      {{ $status === 'all' ? 'bg-slate-900 text-white shadow-sm' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                <span>All</span>
                <span class="{{ $status === 'all' ? 'text-white/80' : 'text-slate-500' }}">
                    ({{ $counts['all'] ?? 0 }})
                </span>
            </a>
        </div>
    </form>
</div>

    {{-- Table --}}
    <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="text-sm font-semibold text-slate-900">Section List</div>
                    <div class="text-xs text-slate-500">
                        @if(request('q'))
                            • Search:
                            <span class="font-medium text-slate-700">"{{ request('q') }}"</span>
                        @endif
                    </div>
                </div>

                <div class="text-xs text-slate-500 text-right">
                    <div>
                        Total shown:
                        <span class="font-medium text-slate-700">{{ $sections->count() }}</span>
                    </div>
                </div>
            </div>
        </div>

        @if($sections->isEmpty())
            <div class="px-6 py-12 text-center">
                <div class="text-sm font-medium text-slate-900">No sections found</div>
                <div class="mt-1 text-xs text-slate-500">Try changing the search or filter, or create a new section.</div>
                <div class="mt-4">
                    <a href="{{ route('sections.create') }}"
                       class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800">
                        + New Section
                    </a>
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold text-slate-600">
                            <th class="px-6 py-3">Section</th>
                            <th class="px-6 py-3">Students</th>
                            <th class="px-6 py-3">Year</th>
                            <th class="px-6 py-3">Term</th>
                            <th class="px-6 py-3">Capacity</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @foreach ($sections as $s)
                            @php
                                $statusTone = $s->status === 'active'
                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                    : 'bg-slate-50 text-slate-700 border-slate-200';
                            @endphp

                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-900">{{ $s->name }}</div>
                                    <div class="text-[11px] text-slate-500">
                                        Year {{ $s->year_level }} • Term {{ $s->term_no }}
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="{{ route('sections.students', $s->id) }}"
                                       class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-900 hover:bg-slate-50">
                                        View Students
                                    </a>
                                </td>

                                <td class="px-6 py-4 text-slate-700 whitespace-nowrap">{{ $s->year_level }}</td>
                                <td class="px-6 py-4 text-slate-700 whitespace-nowrap">{{ $s->term_no }}</td>
                                <td class="px-6 py-4 text-slate-700 whitespace-nowrap">{{ $s->capacity }}</td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full border text-xs font-medium {{ $statusTone }}">
                                        {{ ucfirst($s->status) }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('sections.edit', $s) }}"
                                           class="inline-flex items-center justify-center px-3 py-2 rounded-xl bg-slate-900 text-white text-xs font-medium hover:bg-slate-800">
                                            Edit
                                        </a>

                                        @if ($s->status === 'active')
                                            <form class="inline" method="POST" action="{{ route('sections.promote', $s) }}">
                                                @csrf
                                                <button type="submit"
                                                        class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-900 hover:bg-slate-50">
                                                    Promote
                                                </button>
                                            </form>

                                            <form class="inline" method="POST" action="{{ route('sections.archive', $s) }}">
                                                @csrf
                                                <button type="submit"
                                                        class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-rose-200 bg-rose-50 text-xs font-medium text-rose-700 hover:bg-rose-100">
                                                    Archive
                                                </button>
                                            </form>
                                        @else
                                            <form class="inline" method="POST" action="{{ route('sections.restore', $s) }}">
                                                @csrf
                                                <button type="submit"
                                                        class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-emerald-200 bg-emerald-50 text-xs font-medium text-emerald-700 hover:bg-emerald-100">
                                                    Restore
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-slate-100">
                {{ $sections->links() }}
            </div>
        @endif
    </div>

</div>
