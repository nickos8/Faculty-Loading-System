<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

        {{-- HEADER (dashboard style) --}}
    <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
        <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl font-bold text-slate-900">Student Dashboard</h1>
                <p class="text-sm text-slate-600">Your section, subjects, and today’s classes</p>
            </div>
        </div>
    </div>




        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">

            <x-dashboard.stat-card label="Current Subjects" :value="$currentSubjects" :href="route('student.subjects.index')"/>
            <x-dashboard.stat-card label="Classes Today" :value="$todayMeetings->count()" :href="route('student.schedule.show')" />
            <x-dashboard.stat-card label="Enrollment Status" :value="ucfirst($academic?->enrollment_status ?? 'N/A')" />
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <x-dashboard.section-card title="Today’s Meetings">
                <div class="space-y-3">
                    @forelse($todayMeetings as $meeting)
                        <div class="text-sm">
                            <div class="font-medium">
                                {{ $meeting->offering?->curriculumTermSubject?->subject?->code ?? 'N/A' }}
                                — {{ $meeting->offering?->curriculumTermSubject?->subject?->name ?? 'Unknown subject' }}
                            </div>
                            <div class="text-slate-500">
                               {{ \Carbon\Carbon::parse($meeting->time_start)->format('g:i A') }} -
                                {{ \Carbon\Carbon::parse($meeting->time_end)->format('g:i A') }}
                                • {{ $meeting->room?->name ?? 'No room' }}
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No classes for today.</p>
                    @endforelse
                </div>
            </x-dashboard.section-card>

            <x-dashboard.section-card title="Academic Info">
                <div class="space-y-2 text-sm">
                    <div><span class="text-slate-500">Program:</span> {{ $academic?->program?->program_name ?? 'N/A' }}</div>
                    <div><span class="text-slate-500">Curriculum:</span> {{ $academic?->curriculum?->name ?? 'N/A' }}</div>
                    <div><span class="text-slate-500">Section:</span> {{ $academic?->section?->name ?? 'Unassigned' }}</div>
                </div>
            </x-dashboard.section-card>
        </div>
    </div>
</x-app-layout>
