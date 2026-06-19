@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    {{-- FLASH / ERRORS (dashboard style) --}}
    @if (session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 shadow-sm">
            <div class="font-semibold">Success</div>
            <div class="text-sm mt-1">{{ session('success') }}</div>
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

    {{-- HEADER (dashboard style) --}}
    <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
        <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">Subjects</h1>
                <p class="text-sm text-slate-600">Manage your subject list and prerequisites in one place.</p>
            </div>

             <div class="flex items-center gap-2">
                <a href="{{ route('dashboard') }}"
                   class="px-3 py-2 text-xs font-medium rounded-xl bg-white border border-slate-200 text-slate-900 hover:bg-slate-50">
                     Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    {{-- FILTER / SEARCH (organized layout) --}}
<div class="rounded-2xl border border-slate-200/70 bg-white p-6 shadow-sm">
    <form action="{{ route('subjects.index') }}" method="GET"
          class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">

        {{-- LEFT SIDE: Filters --}}
        <div class="flex flex-col sm:flex-row sm:flex-wrap gap-3 lg:flex-1">

            {{-- Search Input --}}
            <div class="flex-1 min-w-[220px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Search</label>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Search by subject code or name…"
                       class="w-full h-10 rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-900 shadow-sm
                              focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
            </div>

            {{-- Search Button --}}
            <div class="flex items-end">
                <button type="submit"
                        class="h-10 px-4 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800">
                    Search
                </button>
            </div>

            {{-- Clear Button --}}
            @if(request()->filled('search'))
                <div class="flex items-end">
                    <a href="{{ route('subjects.index', ['per_page' => request('per_page', 25)]) }}"
                       class="h-10 inline-flex items-center px-4 rounded-xl border border-slate-200 bg-white text-sm font-medium text-slate-900 hover:bg-slate-50">
                        Clear
                    </a>
                </div>
            @endif

            {{-- Per Page --}}
            <div class="min-w-[110px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Per page</label>
                <select name="per_page"
                        class="w-full h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 shadow-sm
                               focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                        onchange="this.form.submit()">
                    @foreach([25,50,100,200] as $n)
                        <option value="{{ $n }}" @selected((int)request('per_page',25)===$n)>
                            {{ $n }}
                        </option>
                    @endforeach
                </select>
            </div>

        </div>

        {{-- RIGHT SIDE: Primary Action --}}
        <div class="flex justify-start lg:justify-end">
            <a href="{{ route('subjects.create') }}"
               class="h-10 inline-flex items-center px-5 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800 shadow-sm">
                 Create Subject
            </a>
        </div>
    </form>

    {{-- Result count --}}
    <div class="mt-4 text-xs text-slate-500">
        @if($subjects->total())
            Showing <span class="font-medium text-slate-700">{{ $subjects->firstItem() }}–{{ $subjects->lastItem() }}</span>
            of <span class="font-medium text-slate-700">{{ $subjects->total() }}</span> subject(s)
        @else
            Showing <span class="font-medium text-slate-700">0</span> subject(s)
        @endif
        @if(request()->filled('search'))
            for <span class="font-medium text-slate-700">“{{ request('search') }}”</span>
        @endif
    </div>
</div>

    {{-- CONTENT --}}
    @if($subjects->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center shadow-sm">
            <div class="text-sm font-semibold text-slate-900">No subjects found</div>
            <div class="mt-1 text-xs text-slate-500">Try adjusting your search or create a new subject.</div>


        </div>
    @else
        <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
            <div class="px-6 py-4 flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold text-slate-900">Subject List</div>
                    <div class="text-xs text-slate-500">Edit subjects and manage prerequisites.</div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-xs sm:text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold text-slate-600">
                            <th class="px-6 py-3">Code</th>
                            <th class="px-6 py-3">Name</th>
                            <th class="px-6 py-3">Prerequisites</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @foreach ($subjects as $subject)
                            @php($pres = $subject->prerequisites ?? collect())

                            <tr class="hover:bg-slate-50/60">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-medium text-slate-900">{{ $subject->code }}</div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-900">{{ $subject->name }}</div>
                                </td>

                                <td class="px-6 py-4">
                                    @if($pres->isEmpty())
                                        <span class="text-xs text-slate-400 italic">None</span>
                                    @else
                                        <div class="flex flex-wrap gap-1 max-w-[420px]">
                                            @foreach($pres->take(3) as $p)
                                                <span class="text-xs px-2 py-1 rounded-full border border-slate-200 bg-slate-50 text-slate-700">
                                                    {{ $p->code }}
                                                </span>
                                            @endforeach

                                            @if($pres->count() > 3)
                                                <span class="text-xs px-2 py-1 rounded-full border border-slate-200 bg-white text-slate-700"
                                                      title="{{ $pres->pluck('code')->join(', ') }}">
                                                    +{{ $pres->count() - 3 }} more
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <a href="{{ route('subjects.edit', $subject) }}"
                                       class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-900 hover:bg-slate-50">
                                        Edit
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $subjects->withQueryString()->links() }}
            </div>


        </div>

        {{-- INFO CARD --}}
<div class="rounded-2xl border border-blue-100 bg-blue-50/50 p-4">
    <div class="flex gap-3">
        <div class="flex-shrink-0">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div class="flex-1">
            <div class="text-sm font-medium text-blue-900">About Subject Management</div>
            <div class="text-xs text-blue-700 mt-1 space-y-1">
                <p>Use this page to manage subjects and prerequisites.</p>
                <p>Click <strong>Create Subject</strong> to add a new subject.</p>
                <p>Search by subject code or name, then click <strong>Edit</strong> to update details.</p>
                <p><strong>Prerequisites</strong> are subjects that must be completed before taking the selected subject.</p>
            </div>
        </div>
    </div>
</div>
    @endif

</div>
@endsection
