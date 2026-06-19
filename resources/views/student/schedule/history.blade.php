@php
    use Carbon\Carbon;

    $prettyStatus = function ($s) {
        $s = strtolower((string)$s);
        return match ($s) {
            'enrolled' => 'Enrolled',
            'dropped'  => 'Dropped',
            'passed'   => 'Passed',
            'failed'   => 'Failed',
            'credited' => 'Credited',
            default    => ucfirst($s ?: '—'),
        };
    };

    $formatTime = function ($t) {
        if (!$t) return '—';
        return Carbon::parse($t)->format('g:i A'); // 3:00 PM
    };
@endphp

@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    {{-- HEADER (dashboard style) --}}
    <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
        <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">Schedule History</h1>
                <p class="text-sm text-slate-600">Previously added / enrolled class offerings for this student.</p>
            </div>

            @php
                $backUrl = auth()->user()?->hasRole('program_admin')
                    ? route('program-admin.students.classes.index', $student->id)
                    : route('student.schedule.show');
            @endphp

            <div class="flex items-center gap-2">
                <a href="{{ $backUrl }}"
                   class="px-3 py-2 text-xs font-medium rounded-xl bg-white border border-slate-200 text-slate-900 hover:bg-slate-50">
                     Back to Current Schedule
                </a>
            </div>
        </div>
    </div>

    {{-- TABLE CARD --}}
    <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">

        <div class="px-6 py-4 flex items-center justify-between gap-4 flex-wrap">
            <div>
                <div class="text-sm font-semibold text-slate-900">History List</div>
                <div class="text-xs text-slate-500">Shows subject, section, meeting schedule, and the recorded status.</div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-xs sm:text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs font-semibold text-slate-600">
                        <th class="px-6 py-3">Subject</th>
                        <th class="px-6 py-3">Section</th>
                        <th class="px-6 py-3">Schedule</th>
                        <th class="px-6 py-3">Teacher</th>
                        <th class="px-6 py-3">Status</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($history as $enr)
                        @php
                            $off      = $enr->classOffering;
                            $sub      = $off?->curriculumTermSubject?->subject;
                            $sec      = $off?->section;
                            $meetings = $off?->meetings ?? collect();
                        @endphp

                        <tr class="hover:bg-slate-50/60">
                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-900">
                                    {{ $sub?->code ?? '—' }} — {{ $sub?->name ?? '—' }}
                                </div>
                            </td>

                            <td class="px-6 py-4 text-slate-700">
                                {{ $sec?->name ?? '—' }}
                            </td>

                            <td class="px-6 py-4 text-slate-700">
                                @if($meetings->isEmpty())
                                    <span class="text-xs text-slate-400 italic">—</span>
                                @else
                                    <div class="space-y-1">
                                        @foreach($meetings as $m)
                                            <div>
                                                {{ $dayNames[$m->day_of_week] ?? ('Day '.$m->day_of_week) }}
                                                <span class="text-slate-300 mx-1">•</span>
                                                {{ $formatTime($m->time_start) }}–{{ $formatTime($m->time_end) }}
                                                <span class="text-slate-300 mx-1">•</span>
                                                <span class="text-slate-700">{{ $m->room?->name ?? 'TBA' }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>

                            <td class="px-6 py-4 text-slate-700">
                                @if($meetings->isEmpty())
                                    <span class="text-xs text-slate-400 italic">—</span>
                                @else
                                    <div class="space-y-1">
                                        @foreach($meetings as $m)
                                            <div>
                                                {{ $m->teacher?->first_name ?? '—' }}
                                                {{ $m->teacher?->last_name ?? '' }}
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>

                            <td class="px-6 py-4">
                                @php($st = strtolower((string) $enr->status))
                                <span class="inline-flex items-center rounded-full border px-2 py-1 text-xs font-medium
                                    @if(in_array($st, ['passed','credited'], true))

                                    @elseif(in_array($st, ['failed','dropped'], true))

                                    @elseif($st === 'enrolled')

                                    @else
                                      
                                    @endif
                                ">
                                    {{ $prettyStatus($enr->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                No schedule history yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-6 py-4 border-t border-slate-100">
            {{ $history->links() }}
        </div>
    </div>

</div>
@endsection
