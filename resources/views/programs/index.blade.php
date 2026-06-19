@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    {{-- HEADER (dashboard style) --}}
    <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
        <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">Programs</h1>
                <p class="text-sm text-slate-600">Manage programs, durations, terms, and linked curricula.</p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('programs.create') }}"
                   class="px-3 py-2 text-xs font-medium rounded-xl bg-slate-900 text-white hover:bg-slate-800">
                    Create Program
                </a>
            </div>
        </div>
    </div>

    {{-- CONTENT --}}
    @if($programs->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center shadow-sm">
            <div class="text-sm font-semibold text-slate-900">No programs found</div>
            <div class="mt-1 text-xs text-slate-500">Create your first program to get started.</div>

            <div class="mt-5">
                <a href="{{ route('programs.create') }}"
                   class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800">
                    + Create Program
                </a>
            </div>
        </div>
    @else
        <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
            <div class="px-6 py-4 flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold text-slate-900">Program List</div>
                    <div class="text-xs text-slate-500">View and edit program details and curricula.</div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-xs sm:text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold text-slate-600">
                            <th class="px-6 py-3">Program Code</th>
                            <th class="px-6 py-3">Program Name</th>
                            <th class="px-6 py-3">Curriculum</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Duration (Years)</th>
                            <th class="px-6 py-3">Terms</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @foreach ($programs as $program)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-medium text-slate-900">{{ $program->program_code }}</div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-900">{{ $program->program_name }}</div>
                                </td>

                                <td class="px-6 py-4">
                                    @if($program->curriculum)
                                        @php
                                            $c = $program->curriculum;
                                            $label = $c->title ?: $c->code; // fallback if title is NULL
                                        @endphp
                                        <div class="text-sm font-medium text-slate-900">{{ $label }}</div>
                                        <div class="text-xs text-slate-500">{{ $c->code }}</div>
                                    @else
                                        <span class="text-xs px-2 py-1 rounded-full border border-rose-200 bg-rose-50 text-rose-700">
                                            Empty curriculum
                                        </span>
                                    @endif
                                </td>

                                <td class="px-6 py-4">
                                    <span class="text-xs px-2 py-1 rounded-full border border-slate-200 bg-slate-50 text-slate-700 capitalize">
                                        {{ $program->status }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-slate-900">{{ $program->duration }}</div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-slate-900">{{ $program->terms_per_year }}</div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <a href="{{ route('programs.edit', $program->id) }}"
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
                {{ $programs->withQueryString()->links() }}
            </div>
        </div>
    @endif

</div>
@endsection
