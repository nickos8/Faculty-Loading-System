@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    {{-- FLASH / ERRORS (dashboard style) --}}
    @if (session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 shadow-sm">
            <div class="font-semibold">Success</div>
            <div class="text-sm mt-1">{{ session('success') }}</div>
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

    {{-- HEADER (dashboard style) --}}
    <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
        <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">
                    Manage Teacher
                </h1>
                <p class="text-sm text-slate-600">
                    {{ $teacher->last_name }}, {{ $teacher->first_name }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('program-admin.teacher-availabilities.index') }}"
                   class="px-3 py-2 text-xs font-medium rounded-xl bg-white border border-slate-200 text-slate-900 hover:bg-slate-50">
                     Back to teachers
                </a>

                <a href="{{ route('program-admin.teacher-availabilities.create', $teacher) }}"
                   class="px-3 py-2 text-xs font-medium rounded-xl bg-slate-900 text-white hover:bg-slate-800">
                     Add Day
                </a>
            </div>
        </div>
    </div>


    {{-- AVAILABILITY CONTENT --}}
    @if($availabilities->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center shadow-sm">
            <div class="text-sm font-semibold text-slate-900">No availability yet</div>
            <div class="mt-1 text-xs text-slate-500">
                Add at least one day/time range so scheduling can validate teacher conflicts.
            </div>

            <div class="mt-5">
                <a href="{{ route('program-admin.teacher-availabilities.create', $teacher) }}"
                   class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800">
                    + Add Day
                </a>
            </div>
        </div>
    @else
        <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">

            <div class="px-6 py-4 flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold text-slate-900">Availability Rows</div>
                    <div class="text-xs text-slate-500">Edit or remove a day/time window.</div>
                </div>
                <div class="text-xs text-slate-500">
                    Total: <span class="font-medium text-slate-700">{{ $availabilities->count() }}</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-xs sm:text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold text-slate-600">
                            <th class="px-6 py-3">Day</th>
                            <th class="px-6 py-3">Start</th>
                            <th class="px-6 py-3">End</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse($availabilities as $a)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-medium text-slate-900">{{ $a->day }}</div>
                                </td>

                                {{-- add class time-24 so JS can convert --}}
                                <td class="px-6 py-4 whitespace-nowrap text-slate-700 time-24">
                                    {{ $a->start_time }}
                                </td>

                                {{-- add class time-24 so JS can convert --}}
                                <td class="px-6 py-4 whitespace-nowrap text-slate-700 time-24">
                                    {{ $a->end_time }}
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('program-admin.teacher-availabilities.edit', [$teacher, $a]) }}"
                                           class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-900 hover:bg-slate-50">
                                            Edit
                                        </a>

                                        <form class="inline" method="POST"
                                              action="{{ route('program-admin.teacher-availabilities.destroy', [$teacher, $a]) }}"
                                              onsubmit="return confirm('Delete this availability?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-rose-200 bg-rose-50 text-xs font-medium text-rose-700 hover:bg-rose-100">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-slate-500">
                                    No availability yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    @endif

</div>

{{-- JS: convert 24h time (HH:mm or HH:mm:ss) to 12h (h:mm AM/PM) --}}
<script>
    function to12Hour(time) {
        if (!time) return '';

        // supports "HH:mm" or "HH:mm:ss"
        const parts = time.trim().split(':');
        if (parts.length < 2) return time; // if unexpected format, don't change

        let hours = parseInt(parts[0], 10);
        const minutes = parts[1];

        if (Number.isNaN(hours)) return time;

        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12; // 0 -> 12

        return `${hours}:${minutes} ${ampm}`;
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.time-24').forEach(el => {
            const raw = el.textContent.trim();
            el.textContent = to12Hour(raw);
        });
    });
</script>
@endsection
