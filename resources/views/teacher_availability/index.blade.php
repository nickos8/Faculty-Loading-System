@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8 space-y-6">

    {{-- HEADER (Subjects style) --}}
    <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
        <div class="p-6 space-y-1">
            <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">
                Weekly Teaching Availability
            </h1>
            <p class="text-sm text-slate-600">
                This schedule is managed by your Program Administrator and is shown here for reference only.
            </p>
        </div>
    </div>

    {{-- INFO NOTICE (Subjects style) --}}
    <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-blue-800 shadow-sm">
        <div class="font-semibold">View only</div>
        <div class="text-sm mt-1">
            If you need changes to your availability, please contact your Program Admin.
        </div>
    </div>

    {{-- TABLE CARD --}}
    <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
        <div class="px-6 py-4 flex items-center justify-between">
            <div>
                <div class="text-sm font-semibold text-slate-900">Availability</div>
                <div class="text-xs text-slate-500">Weekly availability windows.</div>
            </div>

            <div class="text-xs text-slate-500">
                {{ $availabilities->count() }} day{{ $availabilities->count() === 1 ? '' : 's' }}
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-xs sm:text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs font-semibold text-slate-600">
                        <th class="px-6 py-3">Day</th>
                        <th class="px-6 py-3">Start Time</th>
                        <th class="px-6 py-3">End Time</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($availabilities as $availability)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-6 py-4 font-medium text-slate-900 whitespace-nowrap">
                                {{ $availability->day }}
                            </td>

                            <td class="px-6 py-4 text-slate-700 whitespace-nowrap start-time">
                                {{ $availability->start_time }}
                            </td>

                            <td class="px-6 py-4 text-slate-700 whitespace-nowrap end-time">
                                {{ $availability->end_time }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-10 text-center">
                                <div class="text-sm font-semibold text-slate-900">No availability set</div>
                                <div class="mt-1 text-xs text-slate-500">
                                    No availability has been set for you yet.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@push('scripts')
<script>
    function convertTo12HourFormat(timeStr) {
        const raw = (timeStr || '').trim();
        if (!raw) return raw;

        const parts = raw.split(':'); // supports HH:MM or HH:MM:SS
        if (parts.length < 2) return raw;

        const hour = parseInt(parts[0], 10);
        const minute = parts[1];

        if (Number.isNaN(hour)) return raw;

        let h = hour % 12;
        if (h === 0) h = 12;

        const ampm = hour >= 12 ? 'PM' : 'AM';
        return h + ':' + String(minute).padStart(2, '0') + ' ' + ampm;
    }

    document.querySelectorAll('.start-time').forEach(el => {
        el.innerText = convertTo12HourFormat(el.innerText);
    });

    document.querySelectorAll('.end-time').forEach(el => {
        el.innerText = convertTo12HourFormat(el.innerText);
    });
</script>
@endpush
@endsection
