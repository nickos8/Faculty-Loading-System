<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
        <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
    <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">

        <div class="space-y-1">
            <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">
                Super Admin Dashboard
            </h1>

            <p class="text-sm text-slate-600">
                System-wide overview and quick management access.
            </p>
        </div>

    </div>
</div>
        <div class="grid gap-4 sm:grid-cols-3 xl:grid-cols-4">
    <x-dashboard.stat-card
        label="Programs"
        :value="$totalPrograms"
        :href="route('programs.index')"
    />

    <x-dashboard.stat-card
        label="Active Students"
        :value="$activeStudents"
        :href="route('admin.users.index')"
    />

    <x-dashboard.stat-card
        label="Active Teachers"
        :value="$activeTeachers"
        :href="route('admin.users.index')"
    />

    <x-dashboard.stat-card
        label="Pending Approvals"
        :value="$pendingApprovals"
        :href="route('admin.approvals.index')"
    />

</div>
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
 <x-dashboard.stat-card
        label="Active Sections"
        :value="$activeSections"
        :href="route('sections.index')"
    />


    <x-dashboard.stat-card
        label="Classes Today"
        :value="$classesToday"
    />


    <x-dashboard.stat-card
            label="Available Rooms"
            :value="$activeRooms"
            :href="route('rooms.index')"
    />
</div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-800 mb-4">Enrollment by Program</h2>

                <div class="space-y-3">
                    @forelse($enrollmentByProgram as $row)
                        <div class="flex items-center justify-between text-sm">
                            <span>{{ $row->program_name }}</span>
                            <span class="font-semibold">{{ $row->total_students }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No data available.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-800 mb-4">Recent Users</h2>

                <div class="space-y-3">
                    @forelse($recentUsers as $user)
                        <div class="flex items-center justify-between text-sm">
                            <span>{{ $user->first_name }} {{ $user->last_name }}</span>
                            <span class="capitalize text-slate-500">{{ $user->status }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No recent users.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-800 mb-4">Student Status Summary</h2>

                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span>Active</span>
                        <span class="font-semibold">{{ $activeStudents }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Inactive</span>
                        <span class="font-semibold">{{ $inactiveStudents }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Pending</span>
                        <span class="font-semibold">{{ $pendingStudents }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Declined</span>
                        <span class="font-semibold">{{ $declinedStudents }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Regular</span>
                        <span class="font-semibold">{{ $activeStudentsRegular }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Irregular</span>
                        <span class="font-semibold">{{ $activeStudentsIrregular }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-800 mb-4">Upcoming Classes</h2>

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

            </div>
        </div>
    </div>
</x-app-layout>
