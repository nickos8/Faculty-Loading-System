@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8 space-y-6">

    {{-- HEADER (dashboard style) --}}
    <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
        <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">My Schedule</h1>
                <p class="text-sm text-slate-500">View your current section and class meeting times.</p>
            </div>



            <div class="flex items-center gap-2">
                 <a href="{{ route('student.schedule.show') }}"
                   class="px-3 py-2 text-xs font-medium rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50">
                     button to be edit
                </a>
            </div>
        </div>
    </div>



    @if(!$section && $meetings->isEmpty())
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-800 shadow-sm">
            <div class="font-semibold">No schedule yet</div>
            <div class="text-sm mt-1">You are not assigned to a section yet, or you have no enrolled schedules.</div>
        </div>
    @else



        {{-- SCHEDULE TABLE --}}
        @if($meetings->isEmpty())
            <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-blue-800 shadow-sm">
                <div class="font-semibold">No active schedules</div>
                <div class="text-sm mt-1">No active schedules yet for your enrolled classes.</div>
            </div>
        @else
            <div class="rounded-2xl border border-slate-200 bg-white shadow-xs overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">

                    {{-- SECTION INFO --}}

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-sm text-slate-700">
                    <span class="font-semibold text-slate-900">Section:</span>
                    @if($section)
                         <span class="ml-2 text-slate-700">{{ $section->name }}</span>
                         <span class="ml-2 text-slate-700">({{ $section->program_name }})</span>
                    @else
                        <span class="ml-2 text-slate-500">None (irregular / no section)</span>
                    @endif
                </div>
            </div>



                    <span class="text-xs text-slate-500 flex items-center gap-2">

                         <a href="{{ route('student.schedule.pdf') }}"
                        class="px-3 py-2 text-xs font-medium rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50">
                            Download PDF
                        </a>

                        {{ $meetings->count() }} meeting{{ $meetings->count() === 1 ? '' : 's' }}
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs sm:text-sm">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-semibold text-slate-600">
                                <th class="px-6 py-3">Day</th>
                                <th class="px-6 py-3">Time</th>
                                <th class="px-6 py-3">Subject</th>
                                <th class="px-6 py-3">Teacher</th>
                                <th class="px-6 py-3">Room</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($meetings->sortBy([['day', 'asc'], ['start', 'asc']]) as $m)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-6 py-4 whitespace-nowrap text-slate-700">
                                        {{ $days[$m['day']] ?? $m['day'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="time-24 font-medium text-slate-900">{{ $m['start'] }}</span>
                                        <span class="text-slate-400">–</span>
                                        <span class="time-24 font-medium text-slate-900">{{ $m['end'] }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-slate-900">{{ $m['subject'] }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-slate-700">{{ $m['teacher'] }}</td>
                                    <td class="px-6 py-4 text-slate-700">{{ $m['room'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    @endif

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.time-24').forEach(function (el) {
        const raw = (el.textContent || '').trim();
        if (!raw) return;
        const parts = raw.split(':');
        if (parts.length < 2) return;
        let hour = parseInt(parts[0], 10);
        const minute = parts[1];
        if (isNaN(hour)) return;
        const ampm = hour >= 12 ? 'PM' : 'AM';
        hour = hour % 12 || 12;
        el.textContent = hour + ':' + String(minute).padStart(2, '0') + ' ' + ampm;
    });
});
</script>
@endpush
@endsection
