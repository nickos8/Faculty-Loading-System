@php
    $linkBase = 'gc-nav-link';
    $linkIdle = 'gc-nav-link-idle';
    $linkActive = 'gc-nav-link-active';
@endphp

@php
    $navIcon = '<svg class="gc-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 13h6V4H4v9zm10 7h6v-9h-6v9zM4 20h6v-3H4v3zm10-13h6V4h-6v3z"/></svg>';
    $listIcon = '<svg class="gc-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h11M9 12h11M9 19h11M4 5h.01M4 12h.01M4 19h.01"/></svg>';
    $bookIcon = '<svg class="gc-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.25a6 6 0 00-8-1.5v13a6 6 0 018 1.5m0-13a6 6 0 018-1.5v13a6 6 0 00-8 1.5m0-13v13"/></svg>';
    $peopleIcon = '<svg class="gc-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H2v-2a4 4 0 014-4h3m6-5a4 4 0 11-8 0 4 4 0 018 0zm6 1a3 3 0 00-3-3"/></svg>';
    $calendarIcon = '<svg class="gc-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/></svg>';
    $checkIcon = '<svg class="gc-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
@endphp

<a href="{{ route('dashboard') }}" class="{{ $linkBase }} {{ request()->routeIs('dashboard') ? $linkActive : $linkIdle }}" @click="sidebarOpen=false">
    {!! $navIcon !!}<span>Dashboard</span>
</a>

@auth
    @if(auth()->user()->hasRole('super_admin'))
        <div class="gc-nav-section">Administration</div>
        <a href="{{ url('admin/approvals') }}" class="{{ $linkBase }} {{ request()->is('admin/approvals*') ? $linkActive : $linkIdle }}" @click="sidebarOpen=false">{!! $checkIcon !!}<span>Approvals</span></a>
        <a href="{{ route('programs.index') }}" class="{{ $linkBase }} {{ request()->routeIs('programs.*') ? $linkActive : $linkIdle }}" @click="sidebarOpen=false">{!! $bookIcon !!}<span>Programs</span></a>
        <a href="{{ url('rooms') }}" class="{{ $linkBase }} {{ request()->is('rooms*') ? $linkActive : $linkIdle }}" @click="sidebarOpen=false">{!! $listIcon !!}<span>Rooms</span></a>
        <a href="{{ route('admin.users.index') }}" class="{{ $linkBase }} {{ request()->routeIs('admin.users.*') ? $linkActive : $linkIdle }}" @click="sidebarOpen=false">{!! $peopleIcon !!}<span>User Management</span></a>
    @endif

    @if(auth()->user()->hasRole('program_admin'))
        <div class="gc-nav-section">Academics</div>
        <a href="{{ route('subjects.index') }}" class="{{ $linkBase }} {{ request()->routeIs('subjects.*') ? $linkActive : $linkIdle }}" @click="sidebarOpen=false">{!! $bookIcon !!}<span>Subjects</span></a>
        <a href="{{ url('curricula') }}" class="{{ $linkBase }} {{ request()->is('curricula*') ? $linkActive : $linkIdle }}" @click="sidebarOpen=false">{!! $bookIcon !!}<span>Curriculum</span></a>
        <a href="{{ url('sections') }}" class="{{ $linkBase }} {{ request()->is('sections*') ? $linkActive : $linkIdle }}" @click="sidebarOpen=false">{!! $listIcon !!}<span>Sections</span></a>
        <div class="gc-nav-section">Scheduling</div>
        <a href="{{ url('/admin/schedules/sections') }}" class="{{ $linkBase }} {{ request()->is('admin/schedules/sections*') ? $linkActive : $linkIdle }}" @click="sidebarOpen=false">{!! $calendarIcon !!}<span>Schedules</span></a>
        <a href="{{ route('admin.schedules.offerings.index') }}" class="{{ $linkBase }} {{ request()->routeIs('admin.schedules.offerings.*') ? $linkActive : $linkIdle }}" @click="sidebarOpen=false">{!! $listIcon !!}<span>Offerings</span></a>
        <div class="gc-nav-section">People & Requests</div>
        <a href="{{ route('program-admin.students.index') }}" class="{{ $linkBase }} {{ request()->routeIs('program-admin.students.*') ? $linkActive : $linkIdle }}" @click="sidebarOpen=false">{!! $peopleIcon !!}<span>Students</span></a>
        <a href="{{ url('admin/approvals') }}" class="{{ $linkBase }} {{ request()->is('admin/approvals*') ? $linkActive : $linkIdle }}" @click="sidebarOpen=false">{!! $checkIcon !!}<span>Approvals</span></a>
        <a href="{{ route('program-admin.teacher-availabilities.index') }}" class="{{ $linkBase }} {{ request()->routeIs('program-admin.teacher-availabilities.*') ? $linkActive : $linkIdle }}" @click="sidebarOpen=false">{!! $peopleIcon !!}<span>Faculty Management</span></a>
    @endif

    @if(auth()->user()->hasRole('teacher'))
        <div class="gc-nav-section">Teaching</div>
        <a href="{{ route('teacher.schedule.index') }}" class="{{ $linkBase }} {{ request()->routeIs('teacher.schedule.*') ? $linkActive : $linkIdle }}" @click="sidebarOpen=false">{!! $calendarIcon !!}<span>My Schedule</span></a>
        <a href="{{ route('teacher.evaluations.index') }}" class="{{ $linkBase }} {{ request()->routeIs('teacher.evaluations.*') ? $linkActive : $linkIdle }}" @click="sidebarOpen=false">{!! $checkIcon !!}<span>Evaluations</span></a>
        <a href="{{ route('teacher_availability.index') }}" class="{{ $linkBase }} {{ request()->routeIs('teacher_availability.*') ? $linkActive : $linkIdle }}" @click="sidebarOpen=false">{!! $calendarIcon !!}<span>Availability</span></a>
    @endif

    @if(auth()->user()->hasRole('student'))
        <div class="gc-nav-section">Student</div>
        <a href="{{ url('student/schedule') }}" class="{{ $linkBase }} {{ request()->is('student/schedule*') ? $linkActive : $linkIdle }}" @click="sidebarOpen=false">{!! $calendarIcon !!}<span>Schedule</span></a>
        <a href="{{ route('student.curriculum.index') }}" class="{{ $linkBase }} {{ request()->routeIs('student.curriculum.*') ? $linkActive : $linkIdle }}" @click="sidebarOpen=false">{!! $bookIcon !!}<span>Curriculum</span></a>
        <a href="{{ route('student.subjects.index') }}" class="{{ $linkBase }} {{ request()->routeIs('student.subjects.*') ? $linkActive : $linkIdle }}" @click="sidebarOpen=false">{!! $listIcon !!}<span>Subjects</span></a>
        <a href="{{ route('student.schedule.history') }}" class="{{ $linkBase }} {{ request()->routeIs('student.schedule.history') ? $linkActive : $linkIdle }}" @click="sidebarOpen=false">{!! $calendarIcon !!}<span>Schedule History</span></a>
    @endif
@endauth
