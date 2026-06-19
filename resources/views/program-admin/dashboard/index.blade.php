<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 py-6 space-y-6">


        <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
    <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">

        <div class="space-y-1">
            <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">
                Program Admin Dashboard
            </h1>

            <p class="text-sm text-slate-600">
                Program-based operations snapshot
            </p>
        </div>

    </div>
</div>


      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <x-dashboard.stat-card label="Active Students"
            :value="$activeStudents"
            :href="route('program-admin.students.index')"
            :hint="'Regular: '.$regularCount.' • Irregular: '.$irregularCount"
              />

            <x-dashboard.stat-card label="Pending Approvals"
            :value="$pendingApprovals"
            :href="route('admin.approvals.index')"/>



            <x-dashboard.stat-card label="Active Sections"
            :value="$activeSections"
            :href="route('sections.index')" />


        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-2">

             <x-dashboard.stat-card label="Active Teachers" :value="$activeTeachers" />

             <x-dashboard.stat-card label="Classes Today" :value="$classesToday" />
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <x-dashboard.section-card title="Yearly Active Students">
                @if($yearlyActiveStudents->isEmpty())
                    <p class="text-sm text-slate-500">No data available.</p>
                @else
                    <div class="space-y-3">
                        @foreach($yearlyActiveStudents as $row)
                            <div class="flex items-center justify-between text-sm">
                                <span>{{ $row->year }}</span>
                                <span class="font-semibold">{{ $row->total }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-dashboard.section-card>

            <x-dashboard.section-card title="Classes Today">
    <div class="space-y-5">
        @forelse($upcomingClasses as $offering)
            @php
                $subjectCode = $offering->curriculumTermSubject?->subject?->code ?? 'N/A';
                $teacher = $offering->meetings->sortBy('time_start')->first()?->teacher;
                $teacherName = $teacher
                    ? trim($teacher->first_name . ' ' . $teacher->last_name)
                    : 'No teacher assigned';
                $sectionName = $offering->section?->name ?? 'No section';

                $meeting = $offering->meetings->sortBy('time_start')->first();
                $startTime = $meeting?->time_start
                    ? \Carbon\Carbon::parse($meeting->time_start)->format('h:i A')
                    : 'N/A';
                $endTime = $meeting?->time_end
                    ? \Carbon\Carbon::parse($meeting->time_end)->format('h:i A')
                    : 'N/A';
            @endphp
            <div class="text-sm text-slate-700">
                <span class="font-semibold text-slate-900">{{ $sectionName }}</span>
                <span class="text-slate-400">•</span>
                <span>{{ $teacherName }}</span>
                <span class="text-slate-400">•</span>
                <span class="text-slate-500">{{ $subjectCode }}</span>
                <span class="text-slate-400">•</span>
                <span class="text-slate-500">({{ $startTime }} - {{ $endTime }})</span>
            </div>
        @empty
            <p class="text-sm text-slate-500">No upcoming classes.</p>
        @endforelse
    </div>
</x-dashboard.section-card>
        </div>
    </div>
</x-app-layout>
