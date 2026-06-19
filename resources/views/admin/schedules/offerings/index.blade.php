@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    {{-- FLASH / ERRORS (dashboard style) --}}
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 shadow-sm">
            <div class="font-semibold">Success</div>
            <div class="text-sm mt-1">{{ session('success') }}</div>
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-800 shadow-sm">
            <div class="font-semibold mb-1">Please fix the following:</div>
            <ul class="list-disc list-inside text-sm space-y-1">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- HEADER (dashboard style) --}}
    <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
        <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">Offering Management</h1>
                <p class="text-sm text-slate-600">Search, review, and manage offerings across sections and subjects.</p>
            </div>
        </div>
    </div>

    {{-- FILTER / SEARCH (dashboard style) --}}
    <div class="rounded-2xl border border-slate-200/70 bg-white p-6 shadow-sm">
        <form method="GET" class="grid gap-4 sm:grid-cols-6">
            <div class="sm:col-span-4">
                <label class="block text-xs font-medium text-slate-500 mb-1">Search</label>
                <input type="text"
                       name="q"
                       value="{{ $search ?? '' }}"
                       placeholder="Search section / subject / program…"
                       class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                              focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
            </div>

            <div class="sm:col-span-2 flex items-end gap-2">
                <button type="submit"
                        class="w-full sm:w-auto px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800">
                    Search
                </button>


                @if(!empty($search))
                    <a href="{{ url()->current() }}"
                       class="w-full sm:w-auto px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-medium text-slate-900 hover:bg-slate-50">
                        Clear
                    </a>
                @endif
            </div>
        </form>
        

        <div class="mt-3 text-xs text-slate-500">
            Showing <span class="font-medium text-slate-700">{{ $offerings->count() }}</span> offering(s)
            @if(!empty($search))
                for <span class="font-medium text-slate-700">“{{ $search }}”</span>
            @endif
        </div>
    </div>

    {{-- CONTENT --}}
    @if($offerings->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center shadow-sm">
            <div class="text-sm font-semibold text-slate-900">No offerings found</div>
            <div class="mt-1 text-xs text-slate-500">Try adjusting your search keywords.</div>
        </div>
    @else
        <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
            <div class="px-6 py-4 flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold text-slate-900">Offerings</div>
                    <div class="text-xs text-slate-500">Edit an offering or check its finalization status.</div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-xs sm:text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold text-slate-600">
                            {{--<th class="px-6 py-3">Offering</th>--}}
                            <th class="px-6 py-3">Section</th>
                            <th class="px-6 py-3">Subject</th>
                            <th class="px-6 py-3">Dates</th>
                            <th class="px-6 py-3">Finalization</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                    @forelse($offerings as $o)
                        @php
                            $locked   = $o->finalized_at && !$o->unlocked_at;
                            $unlocked = $o->finalized_at && $o->unlocked_at;
                        @endphp

                        <tr class="hover:bg-slate-50/60">

                            {{-- - <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-slate-900">#{{ $o->offering_id }}</div>
                            </td>--}}
                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-900">{{ $o->section_name }}</div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-900">{{ $o->subject_code }}</div>
                                <div class="text-xs text-slate-500">{{ $o->subject_name }}</div>
                            </td>

                            <td class="px-6 py-4 text-slate-700 whitespace-nowrap">
                                {{ $o->start_date }} → {{ $o->end_date }}
                            </td>

                            <td class="px-6 py-4">
                                @if($locked)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full border border-amber-200 bg-amber-50 text-amber-800 text-xs font-semibold">
                                        Finalized (Locked)
                                    </span>
                                @elseif($unlocked)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full border border-sky-200 bg-sky-50 text-sky-800 text-xs font-semibold">
                                        Unlocked
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full border border-slate-200 bg-slate-50 text-slate-700 text-xs font-semibold">
                                        Draft
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="inline-flex items-center gap-2">
                                    {{-- Reuse your existing edit offering route --}}
                                    <a class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-900 hover:bg-slate-50"
                                       href="{{ route('admin.schedules.sections.offerings.edit', [$o->section_id, $o->offering_id]) }}">
                                        Edit
                                    </a>

                                    <a class="inline-flex items-center justify-center px-3 py-2 rounded-xl bg-slate-900 text-white text-xs font-medium hover:bg-slate-800"
                                       href="{{ route('admin.schedules.offerings.status', $o->offering_id) }}">
                                        Finalization Status
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                No offerings found.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-slate-100">
                {{ $offerings->links() }}
            </div>
        </div>
    @endif

</div>
@endsection
