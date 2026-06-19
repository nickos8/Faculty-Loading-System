{{-- resources/views/program-admin/students/index.blade.php --}}
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
                <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">Students</h1>
                <p class="text-sm text-slate-600">Manage your program’s students and their records.</p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('dashboard') }}"
                   class="px-3 py-2 text-xs font-medium rounded-xl bg-white border border-slate-200 text-slate-900 hover:bg-slate-50">
                     Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    {{-- FILTER / SEARCH (dashboard style) --}}
    <div class="rounded-2xl border border-slate-200/70 bg-white p-6 shadow-sm">

        <form method="GET" action="{{ route('program-admin.students.index') }}"
      class="flex flex-col gap-4 sm:flex-row sm:items-end">

    <!-- Search input + Search button (close together) -->
    <div class="flex-1">
        <label class="block text-xs font-medium text-slate-500 mb-1">Search</label>

        <div class="flex gap-2">
           <input
  type="text"
  name="search"
  placeholder="Search students…"
  value="{{ request('search') }}"
  class="w-full sm:max-w-md rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
         focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
/>


            <button type="submit"
                    class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800">
                Search
            </button>

            @if(request()->filled('search'))
                <a href="{{ route('program-admin.students.index') }}"
                   class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-medium text-slate-900 hover:bg-slate-50">
                    Clear
                </a>
            @endif
        </div>
    </div>

    <!-- BIG SPACE then Create Student -->
    <a href="{{ route('program-admin.students.create') }}"
       class="sm:ml-8 px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800">
        Create Student
    </a>
</form>



        <div class="mt-3 text-xs text-slate-500">
            Showing <span class="font-medium text-slate-700">{{ $students->count() }}</span> student(s)
            @if(request()->filled('search'))
                for <span class="font-medium text-slate-700">“{{ request('search') }}”</span>
            @endif
        </div>
    </div>

    {{-- CONTENT --}}
    @if ($students->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center shadow-sm">
            <div class="text-sm font-semibold text-slate-900">No students found</div>
            <div class="mt-1 text-xs text-slate-500">No students found for your program.</div>

            <div class="mt-5">
                <a href="{{ route('program-admin.students.create') }}"
                   class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800">
                    + Create Student
                </a>
            </div>
        </div>
    @else
        <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
            <div class="px-6 py-4 flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold text-slate-900">Student List</div>
                    <div class="text-xs text-slate-500">Manage curriculum, info, and enrolled classes.</div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-xs sm:text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold text-slate-600">
                            <th class="px-6 py-3">School ID</th>
                            <th class="px-6 py-3">Name</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @foreach ($students as $student)
                            @php
                                $academic = $student->studentAcademic;
                                $sectionName = $academic && $academic->section
                                    ? $academic->section->name
                                    : 'No section';
                            @endphp

                            <tr class="hover:bg-slate-50/60">
                                <td class="px-6 py-4 text-slate-700 whitespace-nowrap">
                                    {{ $student->school_id }}
                                </td>

                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-900">
                                        {{ $student->last_name }}, {{ $student->first_name }}
                                    </div>

                                    {{-- Keep available for later if you want to show it --}}
                                    {{-- <div class="text-xs text-slate-500">Section: {{ $sectionName }}</div> --}}
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="inline-flex flex-wrap justify-end gap-2">
                                        <a href="{{ route('program-admin.students.curriculum.edit', $student->id) }}"
                                           class="inline-flex items-center justify-center px-3 py-2 rounded-xl bg-slate-900 text-white text-xs font-medium hover:bg-slate-800">
                                            Manage Curriculum
                                        </a>

                                        <a href="{{ route('program-admin.students.edit', $student->id) }}"
                                           class="inline-flex items-center justify-center px-3 py-2 rounded-xl bg-slate-900 text-white text-xs font-medium hover:bg-slate-800">
                                            Manage Info
                                        </a>

                                        <a href="{{ route('program-admin.students.classes.index', $student->id) }}"
                                           class="inline-flex items-center justify-center px-3 py-2 rounded-xl bg-slate-900 text-white text-xs font-medium hover:bg-slate-800">
                                            Manage Classes
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-slate-100">
                {{ $students->links() }}
            </div>
        </div>
    @endif

</div>
@endsection
