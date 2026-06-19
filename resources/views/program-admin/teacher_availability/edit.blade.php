{{-- resources/views/program-admin/teacher_availability/edit.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    {{-- FLASH / ERRORS (dashboard style) --}}
    @if ($errors->has('availability'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 shadow-sm">
            <div class="font-semibold mb-1">Action blocked</div>
            <div>{{ $errors->first('availability') }}</div>
        </div>
    @endif

    @if ($errors->any() && !$errors->has('availability'))
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 shadow-sm">
            <div class="font-semibold mb-1">Please fix the following:</div>
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- HEADER (dashboard style) --}}
    <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
        <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">
                    Edit Teacher Availability
                </h1>
                <p class="text-sm text-slate-600">
                    {{ $teacher->first_name }} {{ $teacher->last_name }}
                </p>

                {{-- Meta pills --}}
                <div class="mt-3 flex flex-wrap gap-2 text-xs">
                    <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-slate-700">
                        <span class="text-slate-400">Day</span>
                        <span class="font-semibold text-slate-900">{{ $availability->day }}</span>
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

    {{-- FORM CARD --}}
    <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/60 flex items-center justify-between gap-4 flex-wrap">
            <div>
                <div class="text-sm font-semibold text-slate-900">Availability Details</div>
                <div class="text-xs text-slate-500">Update the time range for this day.</div>
            </div>

            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-slate-200 bg-white text-xs text-slate-700">
                <span class="text-slate-500">Tip</span>
                <span class="font-medium">Must cover scheduled classes</span>
            </span>
        </div>

        <form method="POST"
              action="{{ route('program-admin.teacher-availabilities.update', [$teacher, $availability]) }}"
              class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Start time --}}
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <label class="block text-xs font-semibold text-slate-700">
                        Start time
                    </label>

                    <input type="time"
                           name="start_time"
                           value="{{ old('start_time', substr($availability->start_time, 0, 5)) }}"
                           required
                           class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm">

                    @error('start_time')
                        <div class="mt-2 text-xs text-rose-700">{{ $message }}</div>
                    @enderror
                </div>

                {{-- End time --}}
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <label class="block text-xs font-semibold text-slate-700">
                        End time
                    </label>

                    <input type="time"
                           name="end_time"
                           value="{{ old('end_time', substr($availability->end_time, 0, 5)) }}"
                           required
                           class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm">

                    @error('end_time')
                        <div class="mt-2 text-xs text-rose-700">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 text-xs text-slate-600">
                Availability must cover any already scheduled classes on this day.
                If this update is blocked, reschedule the teacher’s classes first.
            </div>

            <div class="flex flex-col sm:flex-row sm:justify-end gap-2">
                <a href="{{ route('program-admin.teacher-availabilities.show', $teacher) }}"
                   class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-900 text-sm font-medium hover:bg-slate-50">
                    Cancel
                </a>

                <button type="submit"
                        class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-medium shadow-sm hover:bg-slate-800">
                    Update Availability
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
