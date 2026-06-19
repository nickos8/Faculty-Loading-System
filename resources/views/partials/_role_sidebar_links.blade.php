@php
    $linkBase = 'group flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition';
    $linkIdle = 'text-gray-600 hover:text-blue-700 hover:bg-blue-50';
    $linkActive = 'bg-blue-100 text-blue-800';
@endphp

{{-- COMMON --}}
<a href="{{ route('dashboard') }}"
   class="{{ $linkBase }} {{ request()->routeIs('dashboard') ? $linkActive : $linkIdle }}"
   @click="sidebarOpen=false">
    <span>Dashboard</span>
</a>

@auth
    {{-- SUPER ADMIN --}}
    @if(auth()->user()->hasRole('super_admin'))
        <div class="mt-4 px-3 text-xs font-semibold uppercase tracking-wider text-gray-400">
            Admin
        </div>


        <a href="{{ url('admin/approvals') }}"
           class="{{ $linkBase }} {{ request()->is('admin/approvals*') ? $linkActive : $linkIdle }}"
           @click="sidebarOpen=false">
            <span>Approvals</span>
        </a>

        <a href="{{ route('programs.index') }}"
           class="{{ $linkBase }} {{ request()->routeIs('programs.*') ? $linkActive : $linkIdle }}"
           @click="sidebarOpen=false">
            <span>Programs</span>
        </a>

        <a href="{{ url('rooms') }}"
           class="{{ $linkBase }} {{ request()->is('rooms*') ? $linkActive : $linkIdle }}"
           @click="sidebarOpen=false">
            <span>Rooms</span>
        </a>

        <a href="{{ route('admin.users.index') }}"
           class="{{ $linkBase }} {{ request()->routeIs('admin.users.*') ? $linkActive : $linkIdle }}"
           @click="sidebarOpen=false">
            <span>User Management</span>
        </a>
    @endif

    {{-- PROGRAM ADMIN --}}
    @if(auth()->user()->hasRole('program_admin'))


        <div class="mt-4 px-3 text-xs font-semibold uppercase tracking-wider text-gray-400">
            Academics
        </div>

        <a href="{{ route('subjects.index') }}"
           class="{{ $linkBase }} {{ request()->routeIs('subjects.*') ? $linkActive : $linkIdle }}"
           @click="sidebarOpen=false">
            <span>Subjects</span>
        </a>

        <a href="{{ url('curricula') }}"
           class="{{ $linkBase }} {{ request()->is('curricula*') ? $linkActive : $linkIdle }}"
           @click="sidebarOpen=false">
            <span>Curriculum</span>
        </a>

        <a href="{{ url('sections') }}"
           class="{{ $linkBase }} {{ request()->is('sections*') ? $linkActive : $linkIdle }}"
           @click="sidebarOpen=false">
            <span>Sections</span>
        </a>

        <div class="mt-4 px-3 text-xs font-semibold uppercase tracking-wider text-gray-400">
            Scheduling
        </div>

        <a href="{{ url('/admin/schedules/sections') }}"
           class="{{ $linkBase }} {{ request()->is('admin/schedules/sections*') ? $linkActive : $linkIdle }}"
           @click="sidebarOpen=false">
            <span>Schedules</span>
        </a>

        <a href="{{ route('admin.schedules.offerings.index') }}"
           class="{{ $linkBase }} {{ request()->routeIs('admin.schedules.offerings.*') ? $linkActive : $linkIdle }}"
           @click="sidebarOpen=false">
            <span>Offerings</span>
        </a>

        <div class="mt-4 px-3 text-xs font-semibold uppercase tracking-wider text-gray-400">
            People
        </div>

        <a href="{{ route('program-admin.students.index') }}"
           class="{{ $linkBase }} {{ request()->routeIs('program-admin.students.*') ? $linkActive : $linkIdle }}"
           @click="sidebarOpen=false">
            <span>Students</span>
        </a>

        <div class="mt-4 px-3 text-xs font-semibold uppercase tracking-wider text-gray-400">
            Requests
        </div>

        <a href="{{ url('admin/approvals') }}"
           class="{{ $linkBase }} {{ request()->is('admin/approvals*') ? $linkActive : $linkIdle }}"
           @click="sidebarOpen=false">
            <span>Approvals</span>
        </a>

        <div class="mt-4 px-3 text-xs font-semibold uppercase tracking-wider text-gray-400">
    Faculty
</div>

<a href="{{ route('program-admin.teacher-availabilities.index') }}"
   class="{{ $linkBase }} {{ request()->routeIs('program-admin.teacher-availabilities.*') ? $linkActive : $linkIdle }}"
   @click="sidebarOpen=false">
    <span>Faculty Management</span>
</a>
    @endif

    {{-- TEACHER --}}
    @if(auth()->user()->hasRole('teacher'))
        <div class="mt-4 px-3 text-xs font-semibold uppercase tracking-wider text-gray-400">
            Teaching
        </div>

        <a href="{{ route('teacher.schedule.index') }}"
           class="{{ $linkBase }} {{ request()->routeIs('teacher.schedule.*') ? $linkActive : $linkIdle }}"
           @click="sidebarOpen=false">
            <span>My Schedule</span>
        </a>

        <a href="{{ route('teacher.evaluations.index') }}"
           class="{{ $linkBase }} {{ request()->routeIs('teacher.evaluations.*') ? $linkActive : $linkIdle }}"
           @click="sidebarOpen=false">
            <span>Evaluations</span>
        </a>

        <a href="{{ route('teacher_availability.index') }}"
           class="{{ $linkBase }} {{ request()->routeIs('teacher_availability.*') ? $linkActive : $linkIdle }}"
           @click="sidebarOpen=false">
            <span>Availability</span>
        </a>
    @endif

    {{-- STUDENT --}}
    @if(auth()->user()->hasRole('student'))
        <div class="mt-4 px-3 text-xs font-semibold uppercase tracking-wider text-gray-400">
            Student
        </div>

        <a href="{{ url('student/schedule') }}"
           class="{{ $linkBase }} {{ request()->is('student/schedule*') ? $linkActive : $linkIdle }}"
           @click="sidebarOpen=false">
            <span> Schedule</span>
        </a>


        <a href="{{ route('student.curriculum.index') }}"
           class="{{ $linkBase }} {{ request()->routeIs('student.curriculum.*') ? $linkActive : $linkIdle }}"
           @click="sidebarOpen=false">
            <span> Curriculum</span>
        </a>

        <a href="{{ route('student.subjects.index') }}"
           class="{{ $linkBase }} {{ request()->routeIs('student.subjects.*') ? $linkActive : $linkIdle }}"
           @click="sidebarOpen=false">
            <span>Subjects</span>
        </a>

        <a href="{{ route('student.schedule.history') }}"
           class="{{ $linkBase }} {{ request()->routeIs('student.schedule.history') ? $linkActive : $linkIdle }}"
           @click="sidebarOpen=false">
            <span>Schedule History</span>
        </a>


    @endif
@endauth
