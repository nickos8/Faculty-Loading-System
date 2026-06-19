@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-6 space-y-6">

    {{-- FLASH MESSAGES --}}
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
                        Teacher Load Settings
                    </h1>
                    <p class="text-sm text-slate-600 mt-1">
                        Configure teaching load for {{ $teacher->first_name }} {{ $teacher->last_name }}
                    </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('program-admin.teacher-availabilities.index') }}"
                   class="px-3 py-2 text-xs font-medium rounded-xl bg-white border border-slate-200 text-slate-900 hover:bg-slate-50">
                     Back to teachers
                </a>
            </div>
        </div>
    </div>


    {{-- FORM CARD --}}
    <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <div class="text-sm font-semibold text-slate-900">Load Configuration</div>
            <div class="text-xs text-slate-500 mt-1">Set employment type and maximum teaching units</div>
        </div>

        <form method="POST" action="{{ route('program-admin.teacher-load-settings.update', $teacher) }}" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            {{-- EMPLOYMENT TYPE --}}
            <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-900">
                    Employment Type
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="relative flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all employment-type-option
                        {{ old('employment_type', $setting->employment_type) === 'regular'
                            ? 'border-blue-500 bg-blue-50/50'
                            : 'border-slate-200 bg-white hover:border-slate-300' }}">
                        <input type="radio"
                               name="employment_type"
                               value="regular"
                               class="w-4 h-4 text-blue-600 focus:ring-blue-500 employment-type-radio"
                               data-max-units="36" {{-- get the max units here --}}
                               {{ old('employment_type', $setting->employment_type) === 'regular' ? 'checked' : '' }}>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-slate-900">Regular</div>
                            <div class="text-xs text-slate-500 mt-0.5">Full-time (36 units max)</div>
                        </div>
                    </label>

                    <label class="relative flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all employment-type-option
                        {{ old('employment_type', $setting->employment_type) === 'part_time'
                            ? 'border-blue-500 bg-blue-50/50'
                            : 'border-slate-200 bg-white hover:border-slate-300' }}">
                        <input type="radio"
                               name="employment_type"
                               value="part_time"
                               class="w-4 h-4 text-blue-600 focus:ring-blue-500 employment-type-radio"
                               data-max-units="20"
                               {{ old('employment_type', $setting->employment_type) === 'part_time' ? 'checked' : '' }}>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-slate-900">Part-Time</div>
                            <div class="text-xs text-slate-500 mt-0.5">Limited hours (20 units max)</div>
                        </div>
                    </label>
                </div>
                @error('employment_type')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- HIDDEN MAX UNITS INPUT --}}
            <input type="hidden" id="max_units" name="max_units" value="{{ old('max_units', $setting->max_units) }}">

            {{-- SUBMIT BUTTON --}}
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="submit"
                        class="inline-flex items-center justify-center px-6 py-2.5 rounded-xl bg-blue-600 text-sm font-medium text-white hover:bg-blue-700 shadow-sm">
                    Save Settings
                </button>
            </div>
        </form>
    </div>

    {{-- INFO CARD --}}
    <div class="rounded-2xl border border-blue-100 bg-blue-50/50 p-4">
        <div class="flex gap-3">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="flex-1">
                <div class="text-sm font-medium text-blue-900">About Teaching Load</div>
                <div class="text-xs text-blue-700 mt-1 space-y-1">
                    <p>The maximum units are automatically set based on employment type:</p>
                    <p><strong>Regular teachers:</strong> 36 units (full-time load)</p>
                    <p><strong>Part-time teachers:</strong> 20 units (limited hours)</p>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const radios = document.querySelectorAll('.employment-type-radio');
    const maxUnitsInput = document.getElementById('max_units');
    const labels = document.querySelectorAll('.employment-type-option');

    function updateMaxUnits() {
        const selected = document.querySelector('.employment-type-radio:checked');
        if (selected) {
            const maxUnits = selected.getAttribute('data-max-units');
            maxUnitsInput.value = maxUnits;

            // Update border styling
            labels.forEach(label => {
                const radio = label.querySelector('.employment-type-radio');
                if (radio.checked) {
                    label.classList.remove('border-slate-200', 'bg-white');
                    label.classList.add('border-blue-500', 'bg-blue-50/50');
                } else {
                    label.classList.remove('border-blue-500', 'bg-blue-50/50');
                    label.classList.add('border-slate-200', 'bg-white');
                }
            });
        }
    }

    // Update on radio change
    radios.forEach(radio => {
        radio.addEventListener('change', updateMaxUnits);
    });

    // Initialize on page load
    updateMaxUnits();
});
</script>
@endsection
