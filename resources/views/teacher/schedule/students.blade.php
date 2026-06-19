@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8 space-y-6">

    @php
        $section = $classOffering->section;
        $cts     = optional($classOffering->curriculumTermSubject);
        $subject = optional($cts)->subject;

        // ✅ Added: map numeric day_of_week to day name (1=Monday ... 7=Sunday)
        $days = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ];
    @endphp

    {{-- HEADER (Subjects style) --}}
    <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
        <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">
                    Enrolled Students
                </h1>
                <p class="text-sm text-slate-600">
                    View students enrolled in this class offering.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('teacher.schedule.index') }}"
                   class="px-3 py-2 text-xs font-medium rounded-xl bg-white border border-slate-200 text-slate-900 hover:bg-slate-50">
                    ← Back to schedule
                </a>
            </div>
        </div>
    </div>

    {{-- DETAILS (info card) --}}
    <div class="rounded-2xl border border-slate-200/70 bg-white p-6 shadow-sm">
        <div class="grid gap-3 sm:grid-cols-3 text-sm">
            <div>
                <div class="text-xs font-medium text-slate-500">Subject</div>
                <div class="mt-1 text-slate-900">
                    @if($subject)
                        <span class="font-semibold">{{ $subject->code ?? '' }}</span>
                        <span class="text-slate-400">—</span>
                        <span class="text-slate-700">{{ $subject->name ?? '' }}</span>
                    @else
                        <span class="text-slate-400">N/A</span>
                    @endif
                </div>
            </div>

            <div>
                <div class="text-xs font-medium text-slate-500">Section</div>
                <div class="mt-1 text-slate-900">
                    @if($section)
                        <span class="font-semibold">{{ $section->code ?? $section->name ?? '' }}</span>
                    @else
                        <span class="text-slate-400">N/A</span>
                    @endif
                </div>
            </div>

            <div>
                <div class="text-xs font-medium text-slate-500">Schedule</div>
                <div class="mt-1 text-slate-700 space-y-1">
                    @forelse($classOffering->meetings as $meeting)
                        <div class="text-sm">
                            {{-- ✅ Changed: show day name instead of number --}}
                            <span class="font-medium text-slate-900">
                                {{ $days[$meeting->day_of_week] ?? $meeting->day_of_week }}:
                            </span>
                            <span>
                                {{ \Carbon\Carbon::parse($meeting->time_start)->format('h:i A') }}
                                –
                                {{ \Carbon\Carbon::parse($meeting->time_end)->format('h:i A') }}
                            </span>
                        </div>
                    @empty
                        <span class="text-slate-400">No meeting times</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- LIST CARD --}}
    <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
        @php
            $hasStudents = $regularStudents->isNotEmpty() || $additionalStudents->isNotEmpty();
        @endphp

        <div class="px-6 py-4 flex items-center justify-between">
            <div>
                <div class="text-sm font-semibold text-slate-900">Student List</div>
                <div class="text-xs text-slate-500">
                    Regular section students and additional/irregular students are included below.
                </div>
            </div>

            <div class="text-xs text-slate-500">
                Total:
                <span class="font-medium text-slate-700">
                    {{ $regularStudents->count() + $additionalStudents->count() }}
                </span>
            </div>
        </div>

        @if(! $hasStudents)
            <div class="px-6 pb-6">
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-800 shadow-sm">
                    <div class="font-semibold">No students enrolled</div>
                    <div class="text-sm mt-1">No students are currently enrolled in this class offering.</div>
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs sm:text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold text-slate-600">
                            <th class="px-6 py-3">#</th>
                            <th class="px-6 py-3">Student ID</th>
                            <th class="px-6 py-3">Name</th>
                            <th class="px-6 py-3">Email</th>
                            <th class="px-6 py-3">Status</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @php $index = 1; @endphp

                        {{-- REGULAR SECTION STUDENTS --}}
                        @foreach($regularStudents as $student)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-6 py-4 text-slate-500 whitespace-nowrap">
                                    {{ $index++ }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-slate-900">
                                    {{ $student->school_id }}
                                </td>
                                <td class="px-6 py-4 text-slate-900">
                                    <span class="font-medium">{{ $student->last_name }}, {{ $student->first_name }}</span>
                                </td>
                                <td class="px-6 py-4 text-slate-700">
                                    {{ $student->email }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset bg-blue-50 text-blue-700 ring-blue-200">
                                        enrolled
                                    </span>
                                </td>
                            </tr>
                        @endforeach

                        {{-- ADDITIONAL / EXTRA-SCHEDULED STUDENTS --}}
                        @foreach($additionalStudents as $student)
                            @php
                                $status = $student->pivot->status ?? 'enrolled';
                                $isIrregular = !empty($student->pivot->is_additional) && $student->pivot->is_additional;
                            @endphp
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-6 py-4 text-slate-500 whitespace-nowrap">
                                    {{ $index++ }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-slate-900">
                                    {{ $student->school_id }}
                                </td>
                                <td class="px-6 py-4 text-slate-900">
                                    <span class="font-medium">{{ $student->last_name }}, {{ $student->first_name }}</span>
                                </td>
                                <td class="px-6 py-4 text-slate-700">
                                    {{ $student->email }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2 justify-start">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset bg-slate-50 text-slate-700 ring-slate-200">
                                            {{ $status }}
                                        </span>

                                        @if($isIrregular)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full border border-amber-200 bg-amber-50 text-xs text-amber-700">
                                                Irregular
                                            </span>
                                        @endif
                                    </div>
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
