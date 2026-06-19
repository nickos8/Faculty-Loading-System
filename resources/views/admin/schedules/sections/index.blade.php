@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    {{-- HEADER (dashboard style) --}}
    <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
        <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">

                <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">Choose a Section</h1>
                <p class="text-sm text-slate-600">Select a section to manage its schedule.</p>

               {{-- - @if($program)
                    <div class="mt-3 flex flex-wrap gap-2 text-xs">
                        <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-slate-700">
                            <span class="text-slate-400">Program</span>
                            <span class="font-semibold text-slate-900">{{ $program->program_name }}</span>
                        </span>
                    </div>
                @endif- --}}
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.schedules.report.pdf') }}"
                target="_blank"
                class="px-3 py-2 text-xs font-medium rounded-xl bg-red-600 text-white hover:bg-red-700">
                    Download Schedule Report PDF
                </a>

                <a href="{{ route('dashboard') }}"
                class="px-3 py-2 text-xs font-medium rounded-xl bg-white border border-slate-200 text-slate-900 hover:bg-slate-50">
                    Back to Dashboard
                </a>
            </div>

        </div>
    </div>

    {{-- CONTENT --}}
    @if($sections->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center shadow-sm">
            <div class="text-sm font-semibold text-slate-900">No active sections found</div>
            <div class="mt-1 text-xs text-slate-500">No active sections found for your program.</div>

            <div class="mt-5">
                <a href="{{ url()->previous() }}"
                   class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-medium text-slate-900 hover:bg-slate-50">
                    Back
                </a>
            </div>
        </div>
    @else
        <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
            <div class="px-6 py-4 flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold text-slate-900">Section List</div>
                    <div class="text-xs text-slate-500">Choose a section to proceed.</div>
                </div>

                <div class="text-xs text-slate-500">
                    Showing <span class="font-medium text-slate-700">{{ $sections->count() }}</span> section(s)
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-xs sm:text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold text-slate-600">
                            <th class="px-6 py-3">Section</th>
                            <th class="px-6 py-3">Curriculum</th>
                            <th class="px-6 py-3">Year</th>
                            <th class="px-6 py-3">Term</th>
                            <th class="px-6 py-3 text-right">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @foreach($sections as $row)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-900">{{ $row->section_name }}</div>
                                </td>

                                <td class="px-6 py-4 text-slate-700">
                                    {{ $row->curriculum_code }}
                                </td>

                                <td class="px-6 py-4 text-slate-700 whitespace-nowrap">
                                    Year {{ $row->year_level }}
                                </td>

                                <td class="px-6 py-4 text-slate-700 whitespace-nowrap">
                                    Term {{ $row->term_no }}
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <a href="{{ route('admin.schedules.sections.show', $row->id) }}"
                                       class="inline-flex items-center justify-center px-3 py-2 rounded-xl bg-slate-900 text-white text-xs font-medium hover:bg-slate-800">
                                        Manage Schedule
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>
@endsection
