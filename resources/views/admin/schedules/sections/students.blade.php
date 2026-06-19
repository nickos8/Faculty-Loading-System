@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    {{-- FLASH / ERRORS --}}
    @if(session('status'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 shadow-sm">
            <div class="font-semibold">Success</div>
            <div class="text-sm mt-1">{{ session('status') }}</div>
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-800 shadow-sm">
            <div class="font-semibold mb-1">Please fix the following:</div>
            <ul class="list-disc list-inside text-sm space-y-1">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- HEADER --}}
    <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
        <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">

            <div class="space-y-1">
                <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">
                    {{ $section->name }}
                </h1>

                <p class="text-sm text-slate-600">
                    Manage students in this section.
                </p>
            </div>

            <div>
                <a href="{{ route('sections.index') }}"
                   class="px-3 py-2 text-xs font-medium rounded-xl bg-white border border-slate-200 text-slate-900 hover:bg-slate-50">
                    Back to Sections
                </a>
            </div>

        </div>
    </div>

    <form method="POST"
          action="{{ route('admin.schedules.sections.students.batch-update', $section->id) }}"
          onsubmit="return confirm('Are you sure you want to apply these changes to the selected students?');">
        @csrf

        <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">

            {{-- TABLE HEADER --}}
            <div class="px-6 py-4 flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold text-slate-900">Student List</div>
                    <div class="text-xs text-slate-500">
                        Select students and apply bulk changes.
                    </div>
                </div>
            </div>

            {{-- TABLE --}}
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs sm:text-sm">

                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold text-slate-600">

                            <th class="px-6 py-3">
                                <input type="checkbox" id="check-all" class="h-4 w-4">
                            </th>

                            <th class="px-6 py-3">Student</th>
                            <th class="px-6 py-3">School ID</th>
                            <th class="px-6 py-3">Email</th>
                            <th class="px-6 py-3">Gender</th>
                            <th class="px-6 py-3">Academic Status</th>
                            <th class="px-6 py-3">Enrollment Status</th>

                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        @forelse ($students as $student)

                        <tr class="hover:bg-slate-50/60">

                            <td class="px-6 py-4">
                                <input type="checkbox"
                                       name="academic_ids[]"
                                       value="{{ $student->academic_id }}"
                                       class="h-4 w-4 student-checkbox">
                            </td>

                            <td class="px-6 py-4 font-medium text-slate-900">
                                {{ $student->last_name }}, {{ $student->first_name }}
                            </td>

                            <td class="px-6 py-4">
                                {{ $student->school_id }}
                            </td>

                            <td class="px-6 py-4">
                                {{ $student->email }}
                            </td>

                            <td class="px-6 py-4">
                                {{ $student->gender }}
                            </td>

                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full border border-slate-200 bg-slate-50">
                                    {{ ucfirst($student->academic_status) }}
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full border border-slate-200 bg-slate-50">
                                    {{ ucfirst($student->enrollment_status) }}
                                </span>
                            </td>

                        </tr>

                        @empty

                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-slate-500">
                                No students found for this section.
                            </td>
                        </tr>

                        @endforelse

                    </tbody>

                </table>
            </div>


            {{-- BULK ACTIONS --}}
            @if($students->count())

            <div class="border-t border-slate-100 bg-slate-50 px-6 py-4 flex flex-wrap items-end gap-4">

                {{-- SECTION SELECT --}}
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">
                        Move to Section
                    </label>

                    <select id="target_section_id"
                            name="target_section_id"
                            class="w-64 h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 shadow-sm
                                   focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">

                        <option value="">Keep current section</option>

                        @foreach($candidateSections as $sec)
                            <option value="{{ $sec->id }}">
                                {{ $sec->name }}
                            </option>
                        @endforeach

                    </select>
                </div>


                {{-- ENROLLMENT STATUS --}}
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">
                        Change Enrollment Status
                    </label>

                    <select id="enrollment_status"
                            name="enrollment_status"
                            class="w-56 h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 shadow-sm
                                   focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">

                        <option value="">Keep current status</option>

                        @foreach($enrollmentStatuses as $status)
                            <option value="{{ $status }}">
                                {{ ucfirst($status) }}
                            </option>
                        @endforeach

                    </select>
                </div>


                {{-- APPLY BUTTON --}}
                <div class="ml-auto">
                    <button type="submit"
                            class="h-10 px-5 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800 shadow-sm">
                        Apply changes
                    </button>
                </div>

            </div>

            @endif

        </div>

    </form>

</div>


{{-- SELECT ALL SCRIPT --}}
@push('scripts')
<script>

document.addEventListener('DOMContentLoaded', function () {

    const checkAll = document.getElementById('check-all');

    if (!checkAll) return;

    checkAll.addEventListener('change', function (e) {

        const checked = e.target.checked;

        document.querySelectorAll('.student-checkbox').forEach(function (cb) {
            cb.checked = checked;
        });

    });

});

</script>
@endpush

@endsection
