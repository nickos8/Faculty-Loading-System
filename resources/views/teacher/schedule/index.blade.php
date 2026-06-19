@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8 space-y-6">

    {{-- HEADER (Subjects style) --}}
    <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
        <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">
                    My Teaching Schedule
                </h1>

                <p class="text-sm text-slate-600">
                    View your current class meetings and enrolled students.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a  href="{{ route('teacher.schedule.pdf', ['range' => request('range', 'week')]) }}"
                   class="px-3 py-2 text-xs font-medium rounded-xl bg-white border border-slate-200 text-slate-900 hover:bg-slate-50">
                    Download PDF
                </a>
            </div>

        </div>
    </div>

    {{-- LOAD SUMMARY + RANGE SWITCH (card) --}}
    <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
        <div class="p-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="text-xs text-slate-500">
                    Total teaching load ({{ $range === 'month' ? 'per month (approx.)' : 'per week' }}):
                </div>
                <div class="mt-1 text-lg font-semibold text-slate-900">
                    @if($range === 'month')
                        {{ $monthlyHours }}h {{ $monthlyMinutesRemainder }}m
                    @else
                        {{ $weeklyHours }}h {{ $weeklyMinutesRemainder }}m
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('teacher.schedule.index', ['range' => 'week']) }}"
                   class="px-3 py-2 text-xs font-medium rounded-full border
                          {{ $range === 'week' ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                    Weekly
                </a>

                <a href="{{ route('teacher.schedule.index', ['range' => 'month']) }}"
                   class="px-3 py-2 text-xs font-medium rounded-full border
                          {{ $range === 'month' ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                    Monthly
                </a>
            </div>
        </div>

        {{-- CONTENT --}}
        @if($meetings->isEmpty())
            <div class="px-6 pb-6">
                <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-blue-800 shadow-sm">
                    <div class="font-semibold">No active schedule</div>
                    <div class="text-sm mt-1">You currently have no active class meetings assigned.</div>
                </div>
            </div>
        @else
            <div class="border-t border-slate-100"></div>

            <div class="px-6 py-4 flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold text-slate-900">Class Meetings</div>
                    <div class="text-xs text-slate-500">Sorted by day and time.</div>
                </div>
                <div class="text-xs text-slate-500">
                    {{ $meetings->count() }} meeting{{ $meetings->count() === 1 ? '' : 's' }}
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-xs sm:text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold text-slate-600">
                            <th class="px-6 py-3">Day</th>
                            <th class="px-6 py-3">Time</th>
                            <th class="px-6 py-3">Subject</th>
                            <th class="px-6 py-3">Section</th>
                            <th class="px-6 py-3">Room</th>
                            <th class="px-6 py-3 text-right">Students</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @foreach($meetings as $meeting)
                            @php
                                $offering = $meeting->offering;
                                $cts      = optional($offering)->curriculumTermSubject;
                                $subject  = optional($cts)->subject;
                                $section  = optional($offering)->section;
                            @endphp

                            <tr class="hover:bg-slate-50/60">
                                {{-- Day --}}
                                <td class="px-6 py-4 whitespace-nowrap text-slate-900">
                                    {{ $days[$meeting->day_of_week] ?? $meeting->day_of_week }}
                                </td>

                                {{-- Time --}}
                                <td class="px-6 py-4 whitespace-nowrap text-slate-700">
                                    <span class="font-medium text-slate-900">
                                        {{ \Carbon\Carbon::parse($meeting->time_start)->format('h:i A') }}
                                    </span>
                                    <span class="text-slate-400">–</span>
                                    <span class="font-medium text-slate-900">
                                        {{ \Carbon\Carbon::parse($meeting->time_end)->format('h:i A') }}
                                    </span>
                                </td>

                                {{-- Subject --}}
                                <td class="px-6 py-4">
                                    @if($subject)
                                        <div class="font-semibold text-slate-900">
                                            {{ $subject->code ?? '---' }}
                                        </div>
                                        <div class="text-xs text-slate-500">
                                            {{ $subject->name ?? '' }}
                                        </div>
                                    @else
                                        <span class="text-slate-400 text-xs italic">No subject info</span>
                                    @endif
                                </td>

                                {{-- Section --}}
                                <td class="px-6 py-4">
                                    @if($section)
                                        <div class="font-medium text-slate-900">
                                            {{ $section->name }}
                                        </div>
                                    @else
                                        <span class="text-slate-400 text-xs italic">No section</span>
                                    @endif
                                </td>

                                {{-- Room --}}
                                <td class="px-6 py-4">
                                    @if($meeting->room)
                                        <div class="font-medium text-slate-900">
                                            {{ $meeting->room->name }}
                                        </div>
                                    @else
                                        <span class="text-slate-400 text-xs italic">No room</span>
                                    @endif
                                </td>

                                {{-- Students link --}}
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    @if($offering)
                                        <a href="{{ route('teacher.schedule.students', $offering->id) }}"
                                           class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-900 hover:bg-slate-50">
                                            View students
                                        </a>
                                    @else
                                        <span class="text-slate-400 text-xs">N/A</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
@endsection
