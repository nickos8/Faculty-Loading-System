@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    {{-- FLASH --}}
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 shadow-sm">
            <div class="font-semibold">Success</div>
            <div class="text-sm mt-1">{{ session('success') }}</div>
        </div>
    @endif

    {{-- HEADER --}}
    <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
        <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">Curriculum</h1>
                <p class="text-sm text-slate-600">Manage your program curriculum versions and term grids.</p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('dashboard') }}"
                   class="px-3 py-2 text-xs font-medium rounded-xl bg-white border border-slate-200 text-slate-900 hover:bg-slate-50">
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    {{-- FILTERS + SEARCH --}}
    <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm">
        <form method="GET" action="{{ route('curricula.index') }}" class="p-4 sm:p-6 space-y-4">

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">

                {{-- Search --}}
                <div class="lg:col-span-6">
                    <label for="q" class="block text-xs font-medium text-slate-600 mb-1">
                        Search Curriculum
                    </label>
                    <input
                        type="text"
                        name="q"
                        id="q"
                        value="{{ request('q') }}"
                        placeholder="Search by code"
                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm
                               focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                    >
                </div>

                {{-- Sort --}}
                <div class="lg:col-span-2">
                    <label for="sort" class="block text-xs font-medium text-slate-600 mb-1">
                        Sort
                    </label>
                    <select
                        name="sort"
                        id="sort"
                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm
                               focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                    >
                        <option value="latest" {{ request('sort', 'latest') === 'latest' ? 'selected' : '' }}>Newest</option>
                        <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest</option>
                        <option value="code_asc" {{ request('sort') === 'code_asc' ? 'selected' : '' }}>Code A-Z</option>
                        <option value="code_desc" {{ request('sort') === 'code_desc' ? 'selected' : '' }}>Code Z-A</option>
                    </select>
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
                <div class="lg:col-span-1 flex items-end">
                    <a
                        href="{{ route('curricula.index') }}"
                        class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-medium text-slate-700 hover:bg-slate-50"
                    >
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- LIST CARD --}}
    <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
        <div class="px-6 py-4 flex items-center justify-between">
            <div>
                <div class="text-sm font-semibold text-slate-900">Curriculum List</div>
                <div class="text-xs text-slate-500">
                    View curriculum details and term grids.
                    @if(request('q'))
                        <span class="ml-1">Search: "<span class="font-medium text-slate-700">{{ request('q') }}</span>"</span>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-2">


                <a href="{{ route('curricula.create') }}"
                   class="px-3 py-2 text-xs font-medium rounded-xl bg-slate-900 text-white hover:bg-slate-800">
                    Create New Curriculum
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-xs sm:text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs font-semibold text-slate-600">
                        <th class="px-6 py-3">Code</th>
                        <th class="px-6 py-3">Program</th>
                        <th class="px-6 py-3">Created</th>
                        <th class="px-6 py-3 text-right w-[220px]">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($curricula as $c)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-slate-900">{{ $c->code }}</div>
                                @if(!empty($c->title))
                                    <div class="text-xs text-slate-500 mt-0.5">{{ $c->title }}</div>
                                @endif
                            </td>

                            <td class="px-6 py-4">
                                <div class="text-slate-700">{{ $c->program->program_name ?? '—' }}</div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-slate-600">{{ optional($c->created_at)->format('M d, Y') ?? '—' }}</div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('curricula.show', $c->id) }}"
                                       class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-900 hover:bg-slate-50">
                                        View
                                    </a>

                                    {{--
                                    <form action="{{ route('curricula.destroy', $c->id) }}" method="POST"
                                          onsubmit="return confirm('Delete this curriculum?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-rose-200 bg-rose-50 text-xs font-medium text-rose-700 hover:bg-rose-100">
                                            Delete
                                        </button>
                                    </form>
                                    --}}
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <div class="text-sm font-medium text-slate-900">No curricula found</div>
                                <div class="mt-1 text-xs text-slate-500">Try changing the search, or create a new curriculum.</div>
                                <div class="mt-4">
                                    <a href="{{ route('curricula.create') }}"
                                       class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800">
                                        + New Curriculum
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-6 py-4 border-t border-slate-100">
            {{ $curricula->withQueryString()->links() }}
        </div>
    </div>

</div>
@endsection
