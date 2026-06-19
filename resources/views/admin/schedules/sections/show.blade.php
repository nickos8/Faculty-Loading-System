@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    {{-- FLASH / ERRORS --}}
    @if(session('status'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 shadow-sm">
            <div class="font-semibold">Success</div>
            <div class="text-sm mt-1">{{ session('status') }}</div>
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

    {{-- HEADER --}}
<div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
    <div class="px-6 py-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div class="space-y-0.5">
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">
                    Schedule Management
                </h1>
                <span class="text-2xl sm:text-3xl font-light text-slate-400"></span>
                <span class="text-2xl sm:text-3xl font-semibold text-slate-900">
                    {{ $section->name ?? '—' }}
                </span>
            </div>
            <p class="text-sm text-slate-600">
                Create class offerings and assign teachers/rooms based on availability.
            </p>
        </div>

        <div class="shrink-0">
            <a href="{{ url('/admin/schedules/sections') }}"
               class="inline-flex items-center rounded-2xl bg-white px-4 py-2 text-sm text-slate-700 shadow-sm border border-slate-200 hover:bg-slate-50 transition">
                 Back to Sections
            </a>
        </div>

    </div>
</div>

    @php
        $termDatesSet = !empty($term?->start_date) && !empty($term?->end_date);
    @endphp

    @if(!$termDatesSet)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-900 shadow-sm">
            <div class="font-semibold">Term dates are not set</div>
            <div class="text-sm mt-1">
                You must set the curriculum term Start/End dates in Curriculum Term Management before you can save schedules.
            </div>
        </div>
    @endif

    {{-- ADD SCHEDULE (FORM CARD) --}}
   <div class="rounded-2xl border text-slate-900 shadow-sm overflow-hidden bg-slate-300"
         x-data="scheduleForm()"
         x-init="init()"
         x-effect="debouncedRefresh(ctsId,dow,tStart,meetingUnits,tEnd)">

        <form method="POST"
              action="{{ route('admin.schedules.sections.offerings.store', $section->id) }}"
              class="p-6 space-y-6">
            @csrf

            {{-- Hidden dates (optional compatibility). Controller should ignore and use term dates anyway. --}}
            <input type="hidden" name="start_date" value="{{ optional($term?->start_date)->format('Y-m-d') }}">
            <input type="hidden" name="end_date" value="{{ optional($term?->end_date)->format('Y-m-d') }}">

            {{-- SECTION 1: Subject --}}
            <div class="rounded-2xl border border-slate-200 bg-white">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="text-sm font-semibold text-slate-900">Subject</h2>
                    <p class="text-xs text-slate-500 mt-1">Choose the subject. Term date range is enforced automatically.</p>
                </div>

                <div class="p-5 grid gap-4 md:grid-cols-3">
                    {{-- Subject --}}
                    <div class="md:col-span-3">
                        <label class="block text-xs font-medium text-slate-600 mb-1">
                            Subject
                        </label>

                        <select
                            name="curriculum_term_subject_id"
                            x-model="ctsId"
                            @change="onSubjectOrStartChanged()"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm
                                   focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-300"
                            required>
                            <option value="">Select subject…</option>
                            @foreach($requiredSubjects as $s)
                                <option
                                    value="{{ $s->cts_id }}"
                                    data-units="{{ $s->units }}"
                                    data-type="{{ $s->type ?? '' }}"
                                    data-remaining="{{ $s->remaining_minutes ?? ($s->units * 60) }}"
                                >
                                    {{ $s->code }} — {{ $s->name }} ({{ $s->units }} units)
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- SECTION 2: Schedule Details --}}
            <div class="rounded-2xl border border-slate-200 bg-white">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="text-sm font-semibold text-slate-900">Schedule details</h2>
                    <p class="text-xs text-slate-500 mt-1">Set the meeting day and time. End time is automatic.</p>
                </div>

                <div class="p-5 grid gap-4 md:grid-cols-4">
                    {{-- Day --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Day</label>

                        <select
                            name="day_of_week"
                            x-model.number="dow"
                            @change="debouncedRefresh()"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm
                                   focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-300"
                            required>
                            @foreach([0=>'Select', 1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',7=>'Sun'] as $d=>$n)
                                <option value="{{ $d }}">{{ $n }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Start time --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Start time</label>

                        <input type="time"
                               name="time_start"
                               x-model="tStart"
                               @change="onSubjectOrStartChanged()"
                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm
                                      focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-300"
                               required>
                    </div>

                    {{-- Units for this meeting --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Units</label>

                        <select
                            name="meeting_units"
                            x-model.number="meetingUnits"
                            @change="recomputeEnd(); debouncedRefresh()"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm
                                   focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-300"
                            x-html="meetingUnitsOptionsHtml()"
                            required>
                        </select>

                        <p class="mt-2 text-[11px] text-slate-500" x-text="remainingHint"></p>
                    </div>

                    {{-- End time --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">End time</label>

                        <input type="time"
                               name="time_end"
                               x-model="tEnd"
                               readonly
                               class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-900 shadow-sm cursor-not-allowed"
                               required>

                        <p class="mt-2 text-[11px] text-slate-500">Auto-calculated.</p>
                    </div>
                </div>
            </div>

            {{-- SECTION 3: Assignments --}}
            <div class="rounded-2xl border border-slate-200 bg-white">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="text-sm font-semibold text-slate-900">Assignments</h2>
                    <p class="text-xs text-slate-500 mt-1">Select an available teacher and room.</p>
                </div>

                <div class="p-5 grid gap-4 md:grid-cols-4">
                    {{-- Teacher --}}
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-slate-600 mb-1">Teacher</label>

                        <select name="teacher_id"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm disabled:bg-slate-50
                                       focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-300"
                                :disabled="!canFetch() || loadingTeachers"
                                x-html="teacherOptionsHtml()"
                                required>
                        </select>

                        <p class="mt-2 text-[11px] text-slate-500" x-show="loadingTeachers">Loading teachers…</p>
                        <p class="mt-2 text-[11px] text-rose-600" x-show="teacherFetchError" x-text="teacherFetchError"></p>
                    </div>

                    {{-- Room --}}
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-slate-600 mb-1">Room</label>

                        <select name="room_id"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm disabled:bg-slate-50
                                       focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-300"
                                :disabled="!canFetch() || loadingRooms"
                                x-html="roomOptionsHtml()"
                                required>
                        </select>

                        <p class="mt-2 text-[11px] text-slate-500" x-show="loadingRooms">Loading rooms…</p>
                        <p class="mt-2 text-[11px] text-rose-600" x-show="roomFetchError" x-text="roomFetchError"></p>
                    </div>
                </div>
            </div>

            {{-- ACTIONS --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-end gap-3 pt-2">
            <button type="submit"
                    class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-semibold
                        hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="!canSubmit()">
                Save schedule
            </button>

            <a href="{{ route('program-admin.sections.draft-schedule.show', $section->id) }}"
            class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800">
                Auto Draft Schedule
            </a>
        </div>
        </form>
    </div>

    {{-- EXISTING SCHEDULES TABLE (unchanged) --}}
    <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
        <div class="px-6 py-4 flex items-center justify-between gap-4">
            <div>
                <div class="text-sm font-semibold text-slate-900">Existing schedules</div>
                <div class="text-xs text-slate-500">Edit offerings and review meeting details.</div>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.schedules.sections.pdf', $section->id) }}"
                   class="inline-flex items-center px-3 py-2 text-xs font-medium rounded-xl bg-slate-900 text-white hover:bg-slate-800">
                    Download PDF
                </a>

                <div class="text-xs text-slate-500 whitespace-nowrap">
                    {{ $offerings->count() }} total
                </div>
            </div>
        </div>

        @if($offerings->isEmpty())
            <div class="px-6 pb-6">
                <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center">
                    <div class="text-sm font-semibold text-slate-900">No schedules yet</div>
                    <div class="mt-1 text-xs text-slate-500">Create your first offering using the form above.</div>
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-[900px] w-full text-xs sm:text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold text-slate-600">

                            <th class="px-6 py-3">Subject</th>
                            <th class="px-6 py-3">Day</th>
                            <th class="px-6 py-3">Time</th>
                            <th class="px-6 py-3">Teacher</th>
                            <th class="px-6 py-3">Room</th>
                            <th class="px-6 py-3 text-right">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @php
                            $days = [1=>'Monday',2=>'Tuesday',3=>'Wednesday',4=>'Thursday',5=>'Friday',6=>'Saturday',7=>'Sunday'];
                        @endphp

                        @forelse($scheduleRows as $r)
                            <tr class="hover:bg-slate-50/60">


                                <td class="px-6 py-4 font-medium text-slate-900 align-top">
                                    {{ $r->subj_code ?? '—' }} | {{ $r->subj_name ?? '—' }}
                                </td>

                                <td class="px-6 py-4 text-slate-700 whitespace-nowrap">
                                    {{ $days[$r->m->day_of_week] ?? $r->m->day_of_week }}
                                </td>

                                <td class="px-6 py-4 text-slate-700 whitespace-nowrap">
                                    {{ \Illuminate\Support\Str::substr($r->m->time_start,0,5) }}–{{ \Illuminate\Support\Str::substr($r->m->time_end,0,5) }}
                                </td>

                                <td class="px-6 py-4 text-slate-700">
                                    <span class="block max-w-[12rem] truncate">
                                        {{ optional($r->m->teacher)->first_name }}
                                        {{ optional($r->m->teacher)->last_name }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-slate-700 whitespace-nowrap">
                                    {{ optional($r->m->room)->name ?? '—' }}
                                </td>

                                <td class="px-6 py-4 text-right align-top">
                                    <a href="{{ route('admin.schedules.sections.offerings.edit', [$section->id, $r->off->id]) }}?meeting_id={{ $r->m->id }}"
                                       class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-900 hover:bg-slate-50">
                                        Edit
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                    No schedules yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>

@push('scripts')
<script>
function scheduleForm() {
  return {
    ctsId: '',
    dow: '',
    tStart: '',
    meetingUnits: null,
    tEnd: '',

    subjectUnits: 0,
    subjectRemaining: 0,
    remainingHint: '',

    loadingTeachers: false,
    loadingRooms: false,
    teachers: [],
    rooms: [],
    teacherFetchError: '',
    roomFetchError: '',
    to: null,

    termDatesSet: @json($termDatesSet),

    init() {
      this.updateSubjectMeta();
      this.rebuildMeetingUnitsOptions();
      this.recomputeEnd();
      this.maybeRefreshOptions();
    },

    debouncedRefresh() {
      clearTimeout(this.to);
      this.to = setTimeout(() => this.maybeRefreshOptions(), 250);
    },

    canFetch() {
      return this.ctsId && this.dow && this.tStart && this.meetingUnits && this.tEnd;
    },

    canSubmit() {
      return this.canFetch() && this.termDatesSet;
    },

    onSubjectOrStartChanged() {
      this.updateSubjectMeta();
      this.rebuildMeetingUnitsOptions();
      this.recomputeEnd();
      this.maybeRefreshOptions();
    },

    updateSubjectMeta() {
      const sel = document.querySelector('select[name="curriculum_term_subject_id"]');
      const opt = sel?.selectedOptions?.[0];

      if (!opt) {
        this.subjectUnits = 0;
        this.subjectRemaining = 0;
        this.remainingHint = '';
        return;
      }

      this.subjectUnits = Number(opt.dataset.units || 0);
      this.subjectRemaining = Number(opt.dataset.remaining || (this.subjectUnits * 60) || 0);

      const remUnits = this.subjectRemaining / 60;
      this.remainingHint = this.subjectRemaining > 0
        ? `Remaining for this subject: ${this.subjectRemaining} minute(s) (~${this.formatUnits(remUnits)} unit(s))`
        : '';
    },

    rebuildMeetingUnitsOptions() {
      if (!this.subjectRemaining || this.subjectRemaining <= 0) {
        this.meetingUnits = null;
        return;
      }

      const maxUnits = this.subjectRemaining / 60;
      const step = 0.5;
      const floored = Math.floor((maxUnits + 1e-9) / step) * step;

      if (!this.meetingUnits || Number(this.meetingUnits) > maxUnits + 1e-9) {
        this.meetingUnits = Number(Math.min(maxUnits, floored).toFixed(2));
      }
    },

    meetingUnitsOptionsHtml() {
      if (!this.ctsId) return '<option value="">Select subject first</option>';
      if (!this.subjectRemaining || this.subjectRemaining <= 0) return '<option value="">No remaining units</option>';

      const maxUnits = this.subjectRemaining / 60;
      const step = 0.5;

      const opts = [];
      const maxStepped = this.roundToStep(maxUnits, step, false);

      let u = step;
      while (u <= maxStepped + 1e-9) {
        opts.push(u);
        u = Number((u + step).toFixed(10));
      }

      if (Math.abs(maxUnits - maxStepped) > 1e-6) {
        opts.push(Number(maxUnits.toFixed(2)));
      }

      const selectedVal = this.meetingUnits ? Number(this.meetingUnits) : null;

      return opts.map(val => {
        const label = `${this.formatUnits(val)} unit(s) (${Math.round(val * 60)} mins)`;
        const selected = (selectedVal !== null && Math.abs(val - selectedVal) < 1e-6) ? ' selected' : '';
        return `<option value="${val}"${selected}>${label}</option>`;
      }).join('');
    },

    recomputeEnd() {
      if (!this.tStart || !this.meetingUnits) return;
      const endMins = this.hhmmToMinutes(this.tStart) + Math.round(Number(this.meetingUnits) * 60);
      this.tEnd = this.minutesToHHMM(endMins);

      const endInput = document.querySelector('input[name="time_end"]');
      if (endInput) endInput.value = this.tEnd;
    },

    roundToStep(value, step, ceil) {
      const k = value / step;
      return (ceil ? Math.ceil(k) : Math.floor(k)) * step;
    },

    formatUnits(u) {
      return String(Number(u.toFixed(2)));
    },

    hhmmToMinutes(hhmm) {
      const [h, m] = (hhmm || '').split(':').map(v => parseInt(v, 10) || 0);
      return h * 60 + m;
    },

    minutesToHHMM(mins) {
      const t = Math.max(0, Math.min(mins, (24 * 60) - 1));
      const h = String(Math.floor(t / 60)).padStart(2, '0');
      const m = String(t % 60).padStart(2, '0');
      return `${h}:${m}`;
    },

    async maybeRefreshOptions() {
      this.teacherFetchError = '';
      this.roomFetchError = '';

      if (!this.canFetch()) {
        this.teachers = [];
        this.rooms = [];
        return;
      }

      await Promise.all([this.fetchTeachers(), this.fetchRooms()]);
    },

    async fetchTeachers() {
      this.loadingTeachers = true;
      try {
        const url = new URL(@json(route('admin.schedules.sections.available-teachers', $section->id)));
        url.searchParams.set('curriculum_term_subject_id', this.ctsId);
        url.searchParams.set('day_of_week', this.dow);
        url.searchParams.set('time_start', this.tStart);
        url.searchParams.set('time_end', this.tEnd);

        const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });

        if (!res.ok) {
          this.teachers = [];
          if (res.status === 422) this.teacherFetchError = 'Cannot load teachers until term dates are set.';
          return;
        }

        this.teachers = await res.json();
      } finally {
        this.loadingTeachers = false;
      }
    },

    async fetchRooms() {
      this.loadingRooms = true;
      try {
        const url = new URL(@json(route('admin.schedules.sections.available-rooms', $section->id)));
        url.searchParams.set('curriculum_term_subject_id', this.ctsId);
        url.searchParams.set('day_of_week', this.dow);
        url.searchParams.set('time_start', this.tStart);
        url.searchParams.set('time_end', this.tEnd);

        const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });

        if (!res.ok) {
          this.rooms = [];
          if (res.status === 422) this.roomFetchError = 'Cannot load rooms until term dates are set.';
          return;
        }

        this.rooms = await res.json();
      } finally {
        this.loadingRooms = false;
      }
    },

    teacherOptionsHtml() {
      if (!this.ctsId) return '<option value="">Select subject first</option>';
      if (!this.canFetch()) return '<option value="">Fill fields above first</option>';
      if (this.loadingTeachers) return '<option value="">Loading…</option>';
      if (this.teachers.length === 0) return '<option value="">No available teachers</option>';

      return ['<option value="">Choose teacher</option>']
        .concat(this.teachers.map(t => `<option value="${t.id}">${t.name}</option>`))
        .join('');
    },

    roomOptionsHtml() {
      if (!this.ctsId) return '<option value="">Select subject first</option>';
      if (!this.canFetch()) return '<option value="">Fill fields above first</option>';
      if (this.loadingRooms) return '<option value="">Loading…</option>';
      if (this.rooms.length === 0) return '<option value="">No available rooms</option>';

      return ['<option value="">Choose room</option>']
        .concat(this.rooms.map(r => `<option value="${r.id}">${r.name}</option>`))
        .join('');
    },
  }
}
</script>
@endpush

@endsection
