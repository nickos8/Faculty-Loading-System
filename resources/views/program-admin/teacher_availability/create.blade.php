@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    {{-- HEADER (dashboard style) --}}
    <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
        <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">
                    Add Availability Day
                </h1>
                <p class="text-sm text-slate-600">
                    {{ $teacher->last_name }}, {{ $teacher->first_name }}
                </p>

                {{-- Meta pill --}}
                <div class="mt-3 flex flex-wrap gap-2 text-xs">
                    <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-slate-700">
                        <span class="text-slate-400">Teacher</span>
                        <span class="font-semibold text-slate-900">
                            {{ $teacher->last_name }}, {{ $teacher->first_name }}
                        </span>
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('program-admin.teacher-availabilities.show', $teacher) }}"
                   class="px-3 py-2 text-xs font-medium rounded-xl bg-white border border-slate-200 text-slate-900 hover:bg-slate-50">
                    ← Back
                </a>
            </div>
        </div>
    </div>

    {{-- ERRORS --}}
    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 shadow-sm">
            <div class="font-semibold mb-1">Please fix the following:</div>
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- FORM CARD --}}
    <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/60 flex items-center justify-between gap-4 flex-wrap">
            <div>
                <div class="text-sm font-semibold text-slate-900">Availability Details</div>
                <div class="text-xs text-slate-500">Pick a day and time range, then save.</div>
            </div>

            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-slate-200 bg-white text-xs text-slate-700">
                <span class="text-slate-500">Note</span>
                <span class="font-medium">Days already set are disabled</span>
            </span>
        </div>

        <form method="POST"
              action="{{ route('program-admin.teacher-availabilities.store', $teacher) }}"
              class="p-6 space-y-6">
            @csrf

            {{-- Day --}}
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <label class="block text-xs font-semibold text-slate-700">
                    Day
                </label>

                <select name="day"
                        class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm"
                        required>
                    @php $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday']; @endphp
                    @foreach($days as $d)
                        <option value="{{ $d }}" @disabled(in_array($d, $daysWithAvailability ?? []))>
                            {{ $d }} @if(in_array($d, $daysWithAvailability ?? [])) (already set) @endif
                        </option>
                    @endforeach
                </select>

                <div class="mt-2 text-[11px] text-slate-500">
                    If a day is already set, edit it instead of adding a duplicate.
                </div>
            </div>

            {{-- Times --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <label class="block text-xs font-semibold text-slate-700">Start</label>
                    <input type="time"
                           name="start_time"
                           value="{{ old('start_time') }}"
                           class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm"
                           required>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <label class="block text-xs font-semibold text-slate-700">End</label>
                    <input type="time"
                           name="end_time"
                           value="{{ old('end_time') }}"
                           class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm"
                           required>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex flex-col sm:flex-row sm:justify-end gap-2">
                <a href="{{ route('program-admin.teacher-availabilities.show', $teacher) }}"
                   class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-900 text-sm font-medium hover:bg-slate-50">
                    Cancel
                </a>

                <button type="submit"
                        class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-medium shadow-sm hover:bg-slate-800">
                    Save
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
