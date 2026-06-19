@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6 space-y-6">

    {{-- HEADER (dashboard style) --}}
    <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
        <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">Edit schedule</h1>
                <p class="text-sm text-slate-600">
                    Update meeting details, then re-check available teacher and room.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.schedules.sections.show', $sectionId) }}"
                   class="px-3 py-2 text-xs font-medium rounded-xl bg-white border border-slate-200 text-slate-900 hover:bg-slate-50">
                    ← Back to section schedule
                </a>
            </div>
        </div>
    </div>

    {{-- FLASH / ERRORS (dashboard style) --}}
    @if (session('status'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 shadow-sm">
            <div class="font-semibold">Success</div>
            <div class="text-sm mt-1">{{ session('status') }}</div>
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

    {{-- FORM CARD --}}
    <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
        <form method="POST"
              action="{{ route('admin.schedules.sections.offerings.update', [$sectionId, $offering->id]) }}"
              class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <input type="hidden"
                   name="curriculum_term_subject_id"
                   id="cts_id"
                   value="{{ $offering->curriculum_term_subject_id }}">

            <input type="hidden" name="meeting_id" value="{{ $meeting->id }}">

            {{-- ✅ Unified term dates: no UI, keep optional hidden compatibility fields --}}
            <input type="hidden" name="start_date" value="{{ old('start_date', $offering->start_date) }}">
            <input type="hidden" name="end_date" value="{{ old('end_date', $offering->end_date) }}">

            {{-- SECTION: MEETING DETAILS --}}
            <div>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Meeting details</h2>
                        <p class="text-xs text-slate-500 mt-1">
                            End time is auto-calculated from selected units.
                        </p>

                        @php
                            // ===== Units / minutes computation for editing =====
                            $requiredMinutes = (int) round(($effectiveUnits ?? 0) * 60);

                            $toMinutes = function ($t) {
                                if (!$t) return 0;
                                $t = substr($t, 0, 5); // HH:MM
                                [$h,$m] = array_pad(explode(':', $t), 2, 0);
                                return ((int)$h) * 60 + ((int)$m);
                            };

                            $meetingStartMin = $toMinutes(optional($meeting)->time_start);
                            $meetingEndMin   = $toMinutes(optional($meeting)->time_end);
                            $currentMeetingMinutes = max(0, $meetingEndMin - $meetingStartMin);
                            $currentMeetingUnits   = $currentMeetingMinutes ? ($currentMeetingMinutes / 60) : 1.0;

                            $otherMinutes = 0;
                            foreach($offering->meetings as $m) {
                                if ($m->id == $meeting->id) continue;
                                $otherMinutes += max(0, $toMinutes($m->time_end) - $toMinutes($m->time_start));
                            }

                            // Remaining minutes available for THIS meeting when editing (exclude other meetings)
                            $remainingForThisMeetingMinutes = max(0, $requiredMinutes - $otherMinutes);
                            $maxUnitsForThisMeeting = $remainingForThisMeetingMinutes / 60;

                            // Selected units (old input or current meeting duration)
                            $selectedMeetingUnits = (float) old('meeting_units', $currentMeetingUnits);

                            $dayOptions = [1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',7=>'Sun'];
                            $currentDay = old('day_of_week', optional($meeting)->day_of_week);
                        @endphp
                    </div>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-4">
                    {{-- Day of week --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Day of week</label>
                        <select name="day_of_week"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm
                                       focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                                required>
                            <option value="">Select day</option>
                            @foreach($dayOptions as $val => $label)
                                <option value="{{ $val }}" @selected($currentDay == $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Time start --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Time in</label>
                        <input type="time"
                               name="time_start"
                               value="{{ old('time_start', optional($meeting)->time_start ? substr($meeting->time_start, 0, 5) : null) }}"
                               class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                                      focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                               required>
                    </div>

                    {{-- Units for this meeting --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Units (this meeting)</label>

                        <select name="meeting_units"
                                id="meeting_units"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm
                                       focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                                required>
                            @php
                                $step = 0.5;
                                $max = max($step, floor($maxUnitsForThisMeeting / $step) * $step);
                            @endphp

                            @for($u = $step; $u <= $max + 1e-9; $u += $step)
                                @php $mins = (int) round($u * 60); @endphp
                                <option value="{{ number_format($u, 1, '.', '') }}" @selected(abs($selectedMeetingUnits - $u) < 0.001)>
                                    {{ number_format($u, 1) }} unit(s) ({{ $mins }} mins)
                                </option>
                            @endfor
                        </select>
                    </div>

                    {{-- Time end --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Time out</label>
                        <input type="time"
                               name="time_end"
                               value="{{ old('time_end', optional($meeting)->time_end ? substr($meeting->time_end, 0, 5) : null) }}"
                               class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-900 shadow-sm cursor-not-allowed
                                      focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                               readonly
                               required>
                        <p class="mt-2 text-xs text-slate-500">Auto-calculated.</p>
                    </div>
                </div>
            </div>

            {{-- SECTION: ASSIGNMENTS --}}
            <div class="pt-2 border-t border-slate-100">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Assignments</h2>
                        <p class="text-xs text-slate-500 mt-1">Teacher and room refresh based on the schedule window.</p>
                    </div>
                </div>

                @php
                    $currentTeacherId = old('teacher_id', optional($meeting)->teacher_id);
                    $currentRoomId    = old('room_id', optional($meeting)->room_id);
                @endphp

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    {{-- Teacher --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Teacher</label>
                        <select name="teacher_id"
                                id="teacher_id"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm
                                       focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                                required>
                            <option value="">Loading available teachers…</option>
                        </select>
                        <p id="teacher_notice" class="mt-2 text-xs text-rose-600 hidden"></p>
                    </div>

                    {{-- Room --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Room</label>
                        <select name="room_id"
                                id="room_id"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm
                                       focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                                required>
                            <option value="">Loading available rooms…</option>
                        </select>
                        <p id="room_notice" class="mt-2 text-xs text-rose-600 hidden"></p>
                    </div>
                </div>
            </div>

            {{-- ACTIONS --}}
            <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <a href="{{ route('admin.schedules.sections.show', $sectionId) }}"
                   class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-medium text-slate-900 hover:bg-slate-50">
                    Cancel
                </a>

                <button type="submit"
                        class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800">
                    Save changes
                </button>
            </div>
        </form>
    </div>

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Auto-calculate end time based on selected units (meeting_units).
    const startInput = document.querySelector('input[name="time_start"]');
    const endInput   = document.querySelector('input[name="time_end"]');
    const unitsSel   = document.getElementById('meeting_units');

    if (!startInput || !endInput || !unitsSel) return;

    function hhmmToMinutes(hhmm) {
        const [h, m] = (hhmm || '').split(':').map(v => parseInt(v, 10) || 0);
        return h * 60 + m;
    }

    function minutesToHHMM(mins) {
        const total = Math.max(0, Math.min(mins, (24 * 60) - 1));
        const h = String(Math.floor(total / 60)).padStart(2, '0');
        const m = String(total % 60).padStart(2, '0');
        return `${h}:${m}`;
    }

    function getDurationMins() {
        const u = parseFloat(unitsSel.value || '0');
        if (!u || u < 0.5) return 30;
        return Math.round(u * 60);
    }

    function recomputeEnd(triggerRefresh = true) {
        if (!startInput.value) return;
        const startMins = hhmmToMinutes(startInput.value);
        const endMins = startMins + getDurationMins();
        endInput.value = minutesToHHMM(endMins);

        if (triggerRefresh) {
            endInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    startInput.addEventListener('input', () => recomputeEnd(true));
    startInput.addEventListener('change', () => recomputeEnd(true));
    unitsSel.addEventListener('change', () => recomputeEnd(true));

    recomputeEnd(false);
});

// room and teacher options loading
document.addEventListener('DOMContentLoaded', function () {
    const sectionId       = {{ (int) $sectionId }};
    const ctsId           = {{ (int) $offering->curriculum_term_subject_id }};
    const ignoreMeetingId = {{ (int) $meeting->id }};
    const currentTeacherId = {{ (int) ($currentTeacherId ?? 0) }};
    const currentRoomId    = {{ (int) ($currentRoomId ?? 0) }};

    const daySelect = document.querySelector('select[name="day_of_week"]');
    const tStart    = document.querySelector('input[name="time_start"]');
    const tEnd      = document.querySelector('input[name="time_end"]');

    const teacherSelect = document.getElementById('teacher_id');
    const roomSelect    = document.getElementById('room_id');
    const teacherNotice = document.getElementById('teacher_notice');
    const roomNotice    = document.getElementById('room_notice');

    const teachersUrlBase = @json(route('admin.schedules.sections.available-teachers', $sectionId));
    const roomsUrlBase    = @json(route('admin.schedules.sections.available-rooms', $sectionId));

    function haveAllInputs() {
        // ✅ no start/end date dependency anymore
        return ctsId && daySelect.value && tStart.value && tEnd.value;
    }

    function buildUrl(base) {
        const url = new URL(base);
        url.searchParams.set('curriculum_term_subject_id', ctsId);

        // ✅ do NOT send start/end date anymore; server derives from term
        url.searchParams.set('day_of_week', daySelect.value);
        url.searchParams.set('time_start', tStart.value);
        url.searchParams.set('time_end', tEnd.value);
        url.searchParams.set('ignore_meeting_id', ignoreMeetingId);
        return url.toString();
    }

    async function refreshTeachers() {
        if (!haveAllInputs()) {
            teacherSelect.innerHTML = '<option value="">Fill day and time first</option>';
            teacherNotice.classList.add('hidden');
            return;
        }

        teacherSelect.innerHTML = '<option value="">Loading…</option>';
        teacherNotice.classList.add('hidden');

        try {
            const res = await fetch(buildUrl(teachersUrlBase), {
                headers: { 'Accept': 'application/json' }
            });
            const list = res.ok ? await res.json() : [];

            if (!list.length) {
                teacherSelect.innerHTML = '<option value="">No available teachers</option>';
                teacherNotice.textContent = 'No teacher is available for this schedule.';
                teacherNotice.classList.remove('hidden');
                return;
            }

            let html = '<option value="">-- Choose teacher --</option>';
            let canKeepCurrent = false;

            for (const t of list) {
                const selected = (t.id === currentTeacherId) ? ' selected' : '';
                if (selected) canKeepCurrent = true;
                html += `<option value="${t.id}"${selected}>${t.name}</option>`;
            }

            teacherSelect.innerHTML = html;

            if (!canKeepCurrent && currentTeacherId) {
                teacherNotice.textContent =
                    'The previously assigned teacher is not available in this window. Please choose another.';
                teacherNotice.classList.remove('hidden');
            } else {
                teacherNotice.classList.add('hidden');
            }

        } catch (e) {
            teacherSelect.innerHTML = '<option value="">Error loading teachers</option>';
        }
    }

    async function refreshRooms() {
        if (!haveAllInputs()) {
            roomSelect.innerHTML = '<option value="">Fill day and time first</option>';
            roomNotice.classList.add('hidden');
            return;
        }

        roomSelect.innerHTML = '<option value="">Loading…</option>';
        roomNotice.classList.add('hidden');

        try {
            const res = await fetch(buildUrl(roomsUrlBase), {
                headers: { 'Accept': 'application/json' }
            });
            const list = res.ok ? await res.json() : [];

            if (!list.length) {
                roomSelect.innerHTML = '<option value="">No available rooms</option>';
                roomNotice.textContent =
                    'No room is available for this schedule window. Please choose another.';
                roomNotice.classList.remove('hidden');
                return;
            }

            let html = '<option value="">-- Choose room --</option>';
            let canKeepCurrent = false;

            for (const r of list) {
                const selected = (r.id === currentRoomId) ? ' selected' : '';
                if (selected) canKeepCurrent = true;
                html += `<option value="${r.id}"${selected}>${r.name}</option>`;
            }

            roomSelect.innerHTML = html;

            if (!canKeepCurrent && currentRoomId) {
                roomNotice.textContent =
                    'The previously assigned room is not available in this window. Please choose another.';
                roomNotice.classList.remove('hidden');
            } else {
                roomNotice.classList.add('hidden');
            }

        } catch (e) {
            roomSelect.innerHTML = '<option value="">Error loading rooms</option>';
        }
    }

    function refreshAll() {
        refreshTeachers();
        refreshRooms();
    }

    // Trigger refresh when schedule parameters change
    [daySelect, tStart, tEnd].forEach(el => {
        if (el) el.addEventListener('change', refreshAll);
    });

    // Initial population when edit page loads
    refreshAll();
});
</script>
@endpush
@endsection
