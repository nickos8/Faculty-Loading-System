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
                    Teacher Availability
                </h1>
                <p class="text-sm text-slate-600">
                    View teachers in your program and manage their availability, preferred subjects, and load settings. 
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('dashboard') }}"
                   class="px-3 py-2 text-xs font-medium rounded-xl bg-white border border-slate-200 text-slate-900 hover:bg-slate-50">
                     Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    {{-- CONTENT --}}
    @if($teachers->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center shadow-sm">
            <div class="text-sm font-semibold text-slate-900">No teachers found</div>
            <div class="mt-1 text-xs text-slate-500">Once teachers exist in your program, they’ll appear here.</div>
        </div>
    @else
        <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">

            <div class="px-6 py-4 flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold text-slate-900">Teacher List</div>
                    <div class="text-xs text-slate-500">Open a teacher to manage availability rows.</div>
                </div>
                <div class="text-xs text-slate-500">
                    Total: <span class="font-medium text-slate-700">{{ $teachers->count() }}</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-xs sm:text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold text-slate-600">
                            <th class="px-6 py-3">Teacher</th>
                            <th class="px-6 py-3">Availability Rows</th>
                            <th class="px-6 py-3">Employment Type</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                             <th class="px-6 py-3"></th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @foreach($teachers as $t)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-900">
                                        {{ $t->last_name }}, {{ $t->first_name }}
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full border border-slate-200 bg-slate-50 text-xs text-slate-700">
                                        {{ $t->teacher_availabilities_count }}
                                    </span>
                                </td>


                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full border border-slate-200 bg-slate-50 text-xs text-slate-700">
                                        @if($t->teacherLoadSetting?->employment_type === 'part_time')
                                            Part Timer
                                        @elseif($t->teacherLoadSetting?->employment_type === 'regular')
                                            Regular
                                        @else
                                            Not set
                                        @endif
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <a href="{{ route('program-admin.teacher-availabilities.show', $t) }}"
                                       class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-900 hover:bg-slate-50">
                                        Manage Availability
                                    </a>
                                </td>

                                 <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <a href="{{  route('program-admin.teacher-preferred-subjects.show', $t) }}"
                                       class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-900 hover:bg-slate-50">
                                        Manage Preferred Subjects
                                    </a>
                                </td>





                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                     <a href="{{ route('program-admin.teacher-load-settings.show', $t) }}"
                                       class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-900 hover:bg-slate-50">
                                        Load Settings
                                    </a>
                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    @endif

</div>
@endsection
