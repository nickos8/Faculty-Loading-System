@extends('layouts.app')

@section('content')
@php
    $subject = optional(optional($classOffering->curriculumTermSubject)->subject);
    $subjectText = trim(($subject->code ?? '') . ' - ' . ($subject->name ?? ''));
    $sectionText = $classOffering->section->name ?? $classOffering->section->code ?? 'N/A';
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- FINALIZED NOTICE --}}
    @if($isFinalized)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-900 shadow-sm">
            <div class="font-semibold">Finalized (Locked)</div>
            <div class="text-sm mt-1">
                Finalized at {{ optional($finalization->finalized_at)->format('M d, Y h:i A') }}
                by {{ optional($finalization->finalizedBy)->first_name ?? 'N/A' }}
                {{ optional($finalization->finalizedBy)->last_name ?? '' }}
            </div>
        </div>
    @endif

    {{-- HEADER --}}
    <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
        <div class="p-6 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div class="space-y-2">
                <a href="{{ route('teacher.evaluations.index') }}"
                   class="text-sm text-slate-600 hover:text-slate-900 inline-flex items-center gap-2">
                    <span>←</span> Back to My Classes
                </a>

                <div class="space-y-1">
                    <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">
                        Evaluate Students
                    </h1>
                    <p class="text-sm text-slate-600">
                        {{ $subjectText ?: 'Subject not linked' }} • Section: {{ $sectionText }}
                    </p>
                </div>
            </div>

            <div class="w-full lg:w-96">
                <label class="block text-xs font-medium text-slate-500 mb-1" for="search">Search</label>
                <input id="search"
                       type="text"
                       placeholder="Search student..."
                       class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                              focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                       oninput="filterStudents(this.value)">
            </div>
        </div>
    </div>

    {{-- FLASH / ERRORS --}}
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
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- TOOLBAR (count + mark all buttons) --}}
    <div class="rounded-2xl border border-slate-200/70 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm text-slate-600">
                Students enrolled:
                <span class="font-semibold text-slate-900">{{ $enrollments->count() }}</span>
            </div>

            @if(!$isFinalized)
                <div class="flex flex-wrap gap-2">
                    <button type="button"
                            class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-medium text-slate-900 hover:bg-slate-50"
                            onclick="markAll('passed')">
                        Mark all Passed
                    </button>

                    <button type="button"
                            class="px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-medium text-slate-900 hover:bg-slate-50"
                            onclick="markAll('failed')">
                        Mark all Failed
                    </button>
                </div>
            @endif
        </div>

       
    </div>

    {{-- FINALIZE FORM (SEPARATE, NOT NESTED) --}}
    @if(!$isFinalized)
        <form id="finalizeForm" method="POST" action="{{ route('teacher.evaluations.finalize', $classOffering->id) }}">
            @csrf
        </form>
    @endif

    {{-- MAIN FORM --}}
    <form method="POST" action="{{ route('teacher.evaluations.store', $classOffering->id) }}">
        @csrf

        <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs sm:text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold text-slate-600">
                            <th class="px-6 py-3">Student</th>
                            <th class="px-6 py-3">Type</th>
                            <th class="px-6 py-3">Result</th>
                            <th class="px-6 py-3">Remarks</th>
                        </tr>
                    </thead>

                    <tbody id="studentsBody" class="divide-y divide-slate-100">
                        @forelse($enrollments as $enrollment)
                            @php
                                $student = $enrollment->student;
                                $userId = $enrollment->user_id;

                                $academic = $academics[$userId] ?? null;
                                $academicId = $academic->id ?? null;

                                $isAdditional = (int)($enrollment->is_additional ?? 0) === 1;

                                $existing = null;
                                if ($academicId) {
                                    $existing = $isAdditional
                                        ? ($customStatuses[$academicId] ?? null)
                                        : ($officialStatuses[$academicId] ?? null);
                                }

                                $existingStatus = $existing->status ?? null;
                                $existingRemarks = $existing->remarks ?? null;

                                $fullName = trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));
                                $searchText = strtolower($fullName . ' ' . ($student->school_id ?? ''));
                            @endphp

                            <tr class="student-row hover:bg-slate-50/60" data-search="{{ $searchText }}">
                                {{-- Student --}}
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-900">
                                        {{ $fullName ?: 'Unnamed Student' }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        ID: {{ $student->school_id ?? 'N/A' }}
                                    </div>
                                </td>

                                {{-- Type --}}
                                <td class="px-6 py-4">
                                    @if($isAdditional)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset bg-amber-50 text-amber-700 ring-amber-200">
                                            Custom / Additional
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-inset bg-slate-50 text-slate-700 ring-slate-200">
                                            Official
                                        </span>
                                    @endif

                                    @if($existingStatus)
                                        <div class="mt-2 text-xs text-slate-500">
                                            Current: <span class="font-semibold text-slate-700">{{ ucfirst($existingStatus) }}</span>
                                        </div>
                                    @endif
                                </td>

                                {{-- Result --}}
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap items-center gap-4">
                                        <label class="inline-flex items-center gap-2">
                                            <input type="radio"
                                                   class="rounded border-slate-300 text-slate-900 focus:ring-slate-900/10"
                                                   name="results[{{ $userId }}][status]"
                                                   value="passed"
                                                   {{ old("results.$userId.status", $existingStatus) === 'passed' ? 'checked' : '' }}
                                                   {{ $isFinalized ? 'disabled' : '' }}>
                                            <span class="text-sm font-medium text-slate-700">Passed</span>
                                        </label>

                                        <label class="inline-flex items-center gap-2">
                                            <input type="radio"
                                                   class="rounded border-slate-300 text-slate-900 focus:ring-slate-900/10"
                                                   name="results[{{ $userId }}][status]"
                                                   value="failed"
                                                   {{ old("results.$userId.status", $existingStatus) === 'failed' ? 'checked' : '' }}
                                                   {{ $isFinalized ? 'disabled' : '' }}>
                                            <span class="text-sm font-medium text-slate-700">Failed</span>
                                        </label>
                                    </div>

                                    @error("results.$userId.status")
                                        <div class="mt-2 text-xs text-rose-600">{{ $message }}</div>
                                    @enderror
                                </td>

                                {{-- Remarks --}}
                                <td class="px-6 py-4">
                                    <textarea rows="2"
                                              class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                                                     focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                                              name="results[{{ $userId }}][remarks]"
                                              placeholder="Optional remarks..."
                                              {{ $isFinalized ? 'disabled' : '' }}>{{ old("results.$userId.remarks", $existingRemarks) }}</textarea>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-slate-500">
                                    No enrolled students found in this class.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ACTION BAR (Save + Finalize grouped) --}}
            <div class="px-6 py-4 border-t border-slate-100 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
                <div class="text-xs text-slate-500">
                    Save first, then finalize when everything is correct.
                </div>

                <div class="flex flex-col sm:flex-row gap-2 sm:items-center">
                    {{-- Save (primary) --}}
                    <button type="submit"
                            class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800
                                   {{ $isFinalized ? 'opacity-50 cursor-not-allowed' : '' }}"
                            {{ $isFinalized ? 'disabled' : '' }}>
                        Save Evaluations
                    </button>

                    {{-- Finalize (secondary-danger) --}}
                    @if(!$isFinalized)
                        <button type="submit"
                                form="finalizeForm"
                                class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-emerald-600 bg-emerald-50 text-emerald-700 text-sm font-medium hover:bg-emerald-100"
                                onclick="return confirm('Finalize this class? After finalizing, you cannot edit results.');">
                            Finalize Class
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </form>


</div>

@push('scripts')
<script>
function filterStudents(query) {
    const q = (query || '').toLowerCase().trim();
    document.querySelectorAll('#studentsBody .student-row').forEach(row => {
        const hay = (row.getAttribute('data-search') || '');
        row.style.display = hay.includes(q) ? '' : 'none';
    });
}

function markAll(value) {
    document.querySelectorAll('#studentsBody input[type="radio"][value="' + value + '"]').forEach(radio => {
        if (!radio.disabled) radio.checked = true;
    });
}
</script>
@endpush
@endsection
