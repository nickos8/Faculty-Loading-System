@extends('layouts.app')

@section('content')

@php
    $dayNames = [
        1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday',
        5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday',
    ];

    // Small helper for nice labels
    $prettyStatus = function ($s) {
        $s = strtolower((string)$s);
        return match ($s) {
            'passed' => 'Passed',
            'credited' => 'Credited',
            'enrolled' => 'Enrolled',
            'failed' => 'Failed',
            'dropped' => 'Dropped',
            default => 'Not taken',
        };
    };
@endphp

<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    {{-- HEADER (dashboard style) --}}
    <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
        <div class="p-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">
                    Manage Classes
                </h1>
                <div class="text-sm text-slate-700">
                    {{ $student->first_name }} {{ $student->last_name }}

                    @if($studentAcademic)
                        <span class="ml-2 inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-1 text-xs font-medium text-slate-700">
                            {{ ucfirst($studentAcademic->status) }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('program-admin.students.schedule.history', $student->id) }}"
                   class="px-3 py-2 text-xs font-medium rounded-xl bg-white border border-slate-200 text-slate-900 hover:bg-slate-50">
                    View Schedule History
                </a>

                <a href="{{ url()->previous() }}"
                   class="px-3 py-2 text-xs font-medium rounded-xl bg-white border border-slate-200 text-slate-900 hover:bg-slate-50">
                    ← Back
                </a>
            </div>
        </div>
    </div>

    {{-- FLASH / ERRORS (dashboard style) --}}
    @if (session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 shadow-sm">
            <div class="font-semibold">Success</div>
            <div class="text-sm mt-1">{{ session('success') }}</div>
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-800 shadow-sm whitespace-pre-line">
            <div class="font-semibold mb-1">Unable to add class:</div>
            <ul class="list-disc list-inside text-sm space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ================================================= --}}
    {{-- SECTION SCHEDULE (SECTION-BASED)                  --}}
    {{-- ================================================= --}}
    @if ($studentAcademic && $studentAcademic->section_id)
        <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
            <div class="px-6 py-4 flex items-center justify-between gap-3">
                <div>
                    <div class="text-sm font-semibold text-slate-900">
                        Section Schedule
                        <span class="text-xs text-slate-500 font-normal">
                            (Section: {{ $section?->name ?? 'No section assigned' }})
                        </span>
                    </div>
                    <div class="text-xs text-slate-500">Classes based on the student’s current section.</div>
                </div>

                <div class="text-xs text-slate-500">
                    <span class="font-medium text-slate-700">{{ $sectionOfferings->count() }}</span>
                    class{{ $sectionOfferings->count() === 1 ? '' : 'es' }}
                </div>
            </div>

            @if ($sectionOfferings->isEmpty())
                <div class="px-6 py-6 text-sm text-slate-500">
                    This section has no active offerings with schedules.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs sm:text-sm">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-semibold text-slate-600">
                                <th class="px-6 py-3">Subject</th>
                                <th class="px-6 py-3">Meetings</th>
                                <th class="px-6 py-3">Teacher</th>
                                <th class="px-6 py-3">Room</th>
                                <th class="px-6 py-3">Status</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @foreach ($sectionOfferings as $offering)
                                @php
                                    $subject    = $offering->curriculumTermSubject?->subject;
                                    $meetings   = $offering->meetings;
                                    $isEnrolled = in_array($offering->id, $enrolledOfferingIds ?? [], true);
                                @endphp

                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-slate-900">
                                            {{ $subject?->code }} – {{ $subject?->name }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 text-slate-700">
                                        @if ($meetings->isEmpty())
                                            <span class="text-xs text-slate-400 italic">No meetings</span>
                                        @else
                                            <div class="space-y-1">
                                                @foreach ($meetings as $m)
                                                    <div>
                                                        {{ $dayNames[$m->day_of_week] ?? 'Day '.$m->day_of_week }}
                                                        <span class="text-slate-300 mx-1">•</span>
                                                        {{ substr($m->time_start,0,5) }}–{{ substr($m->time_end,0,5) }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 text-slate-700">
                                        @if ($meetings->isEmpty())
                                            <span class="text-xs text-slate-400 italic">—</span>
                                        @else
                                            <div class="space-y-1">
                                                @foreach ($meetings as $m)
                                                    <div>
                                                        {{ optional($m->teacher)->first_name }}
                                                        {{ optional($m->teacher)->last_name }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 text-slate-700">
                                        @if ($meetings->isEmpty())
                                            <span class="text-xs text-slate-400 italic">—</span>
                                        @else
                                            <div class="space-y-1">
                                                @foreach ($meetings as $m)
                                                    <div>{{ optional($m->room)->name ?? '—' }}</div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-1 text-xs font-medium text-slate-700">
                                            {{ $isEnrolled ? 'Enrolled' : 'Available' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif

    {{-- ================================================= --}}
    {{-- Additional / Irregular Schedule                   --}}
    {{-- ================================================= --}}
    <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
        <div class="px-6 py-4 flex items-center justify-between gap-3">
            <div>
                <div class="text-sm font-semibold text-slate-900">Additional / Irregular Schedule</div>
                <div class="text-xs text-slate-500">Classes added outside the student’s section schedule.</div>
            </div>

            <div class="text-xs text-slate-500">
                <span class="font-medium text-slate-700">{{ $enrollments->count() }}</span>
                class{{ $enrollments->count() === 1 ? '' : 'es' }}
            </div>
        </div>

        @if ($enrollments->isEmpty())
            <div class="px-6 py-6 text-sm text-slate-500">
                No classes yet.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs sm:text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold text-slate-600">
                            <th class="px-6 py-3">Subject</th>
                            <th class="px-6 py-3">Section</th>
                            <th class="px-6 py-3">Meetings</th>
                            <th class="px-6 py-3">Teacher</th>
                            <th class="px-6 py-3">Room</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-right w-40"></th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @foreach ($enrollments as $enr)
                            @php
                                $offering  = $enr->classOffering;
                                $subject   = $offering?->curriculumTermSubject?->subject;
                                $meetings  = $offering?->meetings ?? collect();
                                $status    = $enr->status ?? '—';
                            @endphp

                            <tr class="hover:bg-slate-50/60">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-900">
                                        {{ $subject?->code }} – {{ $subject?->name }}
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-slate-700">
                                    {{ $offering?->section?->name ?? '—' }}
                                </td>

                                <td class="px-6 py-4 text-slate-700">
                                    @if ($meetings->isEmpty())
                                        <span class="text-xs text-slate-400 italic">No meetings</span>
                                    @else
                                        <div class="space-y-1">
                                            @foreach ($meetings as $m)
                                                <div>
                                                    {{ $dayNames[$m->day_of_week] ?? 'Day '.$m->day_of_week }}
                                                    <span class="text-slate-300 mx-1">•</span>
                                                    {{ substr($m->time_start, 0, 5) }}–{{ substr($m->time_end, 0, 5) }}
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>

                                <td class="px-6 py-4 text-slate-700">
                                    @if ($meetings->isEmpty())
                                        <span class="text-xs text-slate-400 italic">—</span>
                                    @else
                                        <div class="space-y-1">
                                            @foreach ($meetings as $m)
                                                <div>
                                                    {{ optional($m->teacher)->first_name }}
                                                    {{ optional($m->teacher)->last_name }}
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>

                                <td class="px-6 py-4 text-slate-700">
                                    @if ($meetings->isEmpty())
                                        <span class="text-xs text-slate-400 italic">—</span>
                                    @else
                                        <div class="space-y-1">
                                            @foreach ($meetings as $m)
                                                <div>{{ optional($m->room)->name ?? '—' }}</div>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>

                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-1 text-xs font-medium text-slate-700">
                                        {{ $prettyStatus($status) }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <form method="POST"
                                          action="{{ route('program-admin.students.classes.destroy', [$student->id, $enr->id]) }}"
                                          onsubmit="return confirm('Remove this class from the student?')"
                                          class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-rose-200 bg-rose-50 text-xs font-medium text-rose-700 hover:bg-rose-100">
                                            Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ================================================= --}}
    {{-- Add Class Offering (Search + Add)                 --}}
    {{-- ================================================= --}}
    <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
        <div class="px-6 py-4">
            <div class="text-sm font-semibold text-slate-900">Add Class Offering</div>
            <div class="text-xs text-slate-500">Search available offerings and add as additional class.</div>
        </div>

        <div class="p-6 space-y-4">
            {{-- SEARCH --}}
            <form method="GET"
                  action="{{ route('program-admin.students.classes.index', $student->id) }}"
                  class="grid gap-3 sm:grid-cols-6">
                <div class="sm:col-span-4">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Search</label>
                    <input type="text"
                           name="q"
                           value="{{ $search }}"
                           placeholder="Search by subject code or name…"
                           class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                                  focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                </div>

                <div class="sm:col-span-2 flex items-end gap-2">
                    <button class="w-full sm:w-auto px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800">
                        Search
                    </button>

                    @if(request()->filled('q'))
                        <a href="{{ route('program-admin.students.classes.index', $student->id) }}"
                           class="w-full sm:w-auto px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-medium text-slate-900 hover:bg-slate-50">
                            Clear
                        </a>
                    @endif
                </div>
            </form>

            @if ($search && $availableOfferings->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-6 text-center">
                    <div class="text-sm font-semibold text-slate-900">No offerings found</div>
                    <div class="mt-1 text-xs text-slate-500">No offerings match “{{ $search }}”.</div>
                </div>
            @endif

            @if ($availableOfferings->isNotEmpty())
                <div class="overflow-x-auto rounded-2xl border border-slate-200">
                    <table class="min-w-full text-xs sm:text-sm">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-semibold text-slate-600">
                                <th class="px-6 py-3">Subject</th>
                                <th class="px-6 py-3">Section</th>
                                <th class="px-6 py-3">Meetings</th>
                                <th class="px-6 py-3">Teacher</th>
                                <th class="px-6 py-3">Room</th>
                                <th class="px-6 py-3 text-right w-44"></th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @foreach ($availableOfferings as $offering)
                                @php
                                    $subject  = $offering->curriculumTermSubject?->subject;
                                    $meetings = $offering->meetings;

                                    $pres = $subject?->prerequisites ?? collect();

                                    // Determine missing prerequisites using studentSubjectStatusMap (UI hint)
                                    $missing = [];
                                    foreach ($pres as $p) {
                                        $st = $studentSubjectStatusMap[(int)$p->id] ?? 'not_taken';
                                        if (!in_array($st, ['passed','credited'], true)) {
                                            $missing[] = ['code' => $p->code, 'status' => $st];
                                        }
                                    }
                                    $blocked = count($missing) > 0;
                                @endphp

                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-slate-900">
                                            {{ $subject?->code }} – {{ $subject?->name }}
                                        </div>

                                        {{-- prerequisites --}}
                                        <div class="mt-2 text-xs">
                                            @if ($pres->isEmpty())
                                                <span class="text-slate-400 italic">No prerequisites</span>
                                            @else
                                                <span class="text-slate-500">Prerequisites:</span>
                                                <div class="mt-1 flex flex-wrap gap-1">
                                                    @foreach($pres as $p)
                                                        @php $st = $studentSubjectStatusMap[(int)$p->id] ?? 'not_taken'; @endphp
                                                        <span title="Status: {{ $prettyStatus($st) }}"
                                                              class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-1 text-xs text-slate-700">
                                                            {{ $p->code }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>

                                        @if ($blocked)
                                            <div class="mt-3 rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700">
                                                <span class="font-semibold">Blocked:</span> Missing prerequisite completion
                                                <div class="mt-2 flex flex-wrap gap-1">
                                                    @foreach($missing as $m)
                                                        <span class="inline-flex items-center rounded-full border border-rose-200 bg-white px-2 py-1 text-xs text-rose-700">
                                                            {{ $m['code'] }} ({{ $prettyStatus($m['status']) }})
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 text-slate-700">
                                        {{ $offering->section?->name ?? '—' }}
                                    </td>

                                    <td class="px-6 py-4 text-slate-700">
                                        @if ($meetings->isEmpty())
                                            <span class="text-xs text-slate-400 italic">No meetings</span>
                                        @else
                                            <div class="space-y-1">
                                                @foreach ($meetings as $m)
                                                    <div>
                                                        {{ $dayNames[$m->day_of_week] ?? 'Day '.$m->day_of_week }}
                                                        <span class="text-slate-300 mx-1">•</span>
                                                        {{ substr($m->time_start, 0, 5) }}–{{ substr($m->time_end, 0, 5) }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 text-slate-700">
                                        @if ($meetings->isEmpty())
                                            <span class="text-xs text-slate-400 italic">—</span>
                                        @else
                                            <div class="space-y-1">
                                                @foreach ($meetings as $m)
                                                    <div>
                                                        {{ optional($m->teacher)->first_name }}
                                                        {{ optional($m->teacher)->last_name }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 text-slate-700">
                                        @if ($meetings->isEmpty())
                                            <span class="text-xs text-slate-400 italic">—</span>
                                        @else
                                            <div class="space-y-1">
                                                @foreach ($meetings as $m)
                                                    <div>{{ optional($m->room)->name ?? '—' }}</div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 text-right">
                                        <form method="POST"
                                              action="{{ route('program-admin.students.classes.store', $student->id) }}"
                                              class="inline">
                                            @csrf
                                            <input type="hidden" name="class_offering_id" value="{{ $offering->id }}">
                                            <input type="hidden" name="is_additional" value="1">

                                            @if($blocked)
                                                <button type="button"
                                                        disabled
                                                        title="Blocked: prerequisites not satisfied"
                                                        class="inline-flex items-center justify-center px-3 py-2 rounded-xl bg-slate-300 text-white text-xs font-medium cursor-not-allowed">
                                                    Blocked
                                                </button>
                                            @else
                                                <button class="inline-flex items-center justify-center px-3 py-2 rounded-xl bg-slate-900 text-white text-xs font-medium hover:bg-slate-800">
                                                    Add class
                                                </button>
                                            @endif
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

        </div>
    </div>

</div>
@endsection
