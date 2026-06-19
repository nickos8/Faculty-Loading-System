<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
         {{-- HEADER (dashboard style) --}}
    <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
        <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl font-bold text-slate-900">Teacher Dashboard</h1>
                <p class="text-sm text-slate-600">Your schedule and evaluation workload</p>
            </div>
        </div>
    </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-dashboard.stat-card label="Classes Today" :value="$classesToday" />
            <x-dashboard.stat-card label="Active Classes" :value="$activeClasses" />
            <x-dashboard.stat-card label="Pending Evaluations" :value="$pendingEvaluations" />
            <x-dashboard.stat-card label="Weekly Load" :value="$weeklyHours.'h '.$weeklyMinutes.'m'" />
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <x-dashboard.section-card title="Next Class">
                @if($nextClass)
                    <div class="text-sm">
                        <div class="font-medium">
                            {{ $nextClass->offering?->curriculumTermSubject?->subject?->code ?? 'N/A' }}
                            — {{ $nextClass->offering?->curriculumTermSubject?->subject?->name ?? 'Unknown subject' }}
                        </div>
                        <div class="text-slate-500 mt-1">
                            {{ $nextClass->offering?->section?->name ?? 'No section' }}
                            • {{ substr($nextClass->time_start, 0, 5) }} - {{ substr($nextClass->time_end, 0, 5) }}
                        </div>
                    </div>
                @else
                    <p class="text-sm text-slate-500">No upcoming class found.</p>
                @endif
            </x-dashboard.section-card>

            <x-dashboard.section-card title="Weekly Meetings">
    <div class="space-y-3">
        @forelse($meetings->take(6) as $meeting)
            <div class="text-sm">
                <div class="font-medium">
                    {{ $meeting->offering?->curriculumTermSubject?->subject?->code ?? 'N/A' }}
                    — {{ $meeting->offering?->curriculumTermSubject?->subject?->name ?? 'Unknown subject' }}
                </div>

                <div class="text-slate-500">
                    {{ $meeting->offering?->section?->name ?? 'No section' }}
                    • Room: {{ $meeting->room?->name ?? 'No room' }}
                </div>

                <div class="text-slate-500">
                    {{ \Carbon\Carbon::parse($meeting->time_start)->format('g:i A') }} -
                      {{ \Carbon\Carbon::parse($meeting->time_end)->format('g:i A') }}
                </div>
            </div>
        @empty
            <p class="text-sm text-slate-500">No meetings available.</p>
        @endforelse
    </div>
</x-dashboard.section-card>
        </div>
    </div>
</x-app-layout>
