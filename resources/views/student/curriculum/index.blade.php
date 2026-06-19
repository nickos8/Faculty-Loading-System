@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8 space-y-6">





    {{-- HEADER (dashboard style) --}}
    <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
        <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
               <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                    My Curriculum
                </h1>
                <p class="text-sm text-slate-500">
                    View all subjects in your curriculum (including custom subjects) and their current status.
                </p>
            </div>


            <div class="flex items-center gap-2">
                 <a href="{{ route('student.schedule.show') }}"
                   class="px-3 py-2 text-xs font-medium rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50">
                     button to be edit
                </a>
            </div>
        </div>
    </div>



    {{-- Academic Info/ LEGEND / INFO  --}}
    <div class="rounded-2xl border border-slate-200/70 bg-white p-6 shadow-sm">
        <div class="text-sm font-semibold text-slate-900">ACADEMIC INFORMATION</div>
        <div class="mt-2 grid gap-2 sm:grid-cols-2 text-xs text-slate-600">
            <div class="flex items-start gap-2">


        @if ($academic)
            <div class="flex flex-col gap-1 text-sm">

                <span class="text-slate-500">
                    Name        :
                    <span class="font-medium text-slate-700">
                        {{ $academic->student->first_name ?? '' }}
                        {{ $academic->student->last_name ?? '' }}
                    </span>
                </span>



                <span class="text-slate-500">
                    Program:
                    <span class="font-medium text-slate-700">
                        {{ $academic->program->program_name ?? $academic->program->name ?? 'N/A' }}
                    </span>
                </span>
                <span class="text-slate-500">
                    Curriculum:
                    <span class="font-medium text-slate-700">
                        @if ($academic->curriculum)
                            {{ $academic->curriculum->code }} – {{ $academic->curriculum->title }}
                        @else
                            N/A
                        @endif
                    </span>
                </span>
            </div>
        @endif

            </div>

            <div class="flex items-start gap-2">
                <span class="inline-flex items-center px-2 py-1 rounded-full border border-indigo-200 bg-indigo-50 text-indigo-700">
                    Custom
                </span>
                <span class="text-slate-500">Added by your program for your record</span>

                <span class="inline-flex items-center px-2 py-1 rounded-full border border-slate-200 bg-slate-50 text-slate-700">
                    Official
                </span>
                <span class="text-slate-500">From your official program curriculum</span>
            </div>
        </div>
    </div>

    {{-- CURRICULUM BY TERM --}}
    @forelse ($groupedTerms as $termLabel => $rows)
        @php
            $totalUnits = $rows->sum(fn ($r) => $r->units ?? 0);
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
                            <th class="px-6 py-3 w-44">Remarks</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @foreach ($rows as $row)
                            @php
                                switch ($row->status) {
                                    case 'passed':
                                        $statusLabel = 'Passed';
                                        $statusClass = 'bg-emerald-50 text-emerald-700 ring-emerald-200';
                                        break;
                                    case 'failed':
                                        $statusLabel = 'Failed';
                                        $statusClass = 'bg-rose-50 text-rose-700 ring-rose-200';
                                        break;
                                    case 'enrolled':
                                        $statusLabel = 'Enrolled';
                                        $statusClass = 'bg-blue-50 text-blue-700 ring-blue-200';
                                        break;
                                    case 'credited':
                                        $statusLabel = 'Credited';
                                        $statusClass = 'bg-amber-50 text-amber-700 ring-amber-200';
                                        break;
                                    default:
                                        $statusLabel = 'Not taken';
                                        $statusClass = 'bg-slate-50 text-slate-600 ring-slate-200';
                                        break;
                                }

                                $isCustom = $row->is_custom ?? false;
                            @endphp

                            <tr class="hover:bg-slate-50/60">

                                {{-- CODE --}}
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-900">{{ $row->code }}</div>
                                </td>

                                {{-- ✅ SUBJECT: break-words allows long names to wrap cleanly --}}
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-900 break-words leading-snug">
                                        {{ $row->name }}
                                    </div>
                                    <div class="mt-1">
                                        @if ($isCustom)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full border border-indigo-200 bg-indigo-50 text-xs text-indigo-700">
                                                Custom
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-1 rounded-full border border-slate-200 bg-slate-50 text-xs text-slate-700">
                                                Official
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- UNITS --}}
                                <td class="px-6 py-4 text-center whitespace-nowrap text-slate-700">
                                    {{ $row->units !== null ? $row->units : '—' }}
                                </td>

                                {{-- TYPE --}}
                                <td class="px-6 py-4 text-center whitespace-nowrap text-slate-700">
                                    {{ $row->type ? strtoupper($row->type) : '—' }}
                                </td>

                                {{-- STATUS --}}
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ">
                                        {{ $statusLabel }}
                                    </span>
                                </td>

                                {{-- REMARKS --}}
                                <td class="px-6 py-4 text-slate-600">
                                    @if ($row->remarks)
                                        <div class="text-xs break-words">{{ $row->remarks }}</div>
                                    @elseif ($row->status === 'credited')
                                        <div class="text-xs text-slate-400 italic">Credited by program/department.</div>
                                    @elseif ($row->status === 'not_taken' || $row->status === null)
                                        <span class="text-xs text-slate-400">No remarks.</span>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
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
                No curriculum subjects found for your record yet. Please contact your program department.
            </div>
        </div>
    @endforelse

    <div class="h-6"></div>
</div>
@endsection
