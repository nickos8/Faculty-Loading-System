@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6 space-y-6">

    {{-- HEADER (dashboard style) --}}
    <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
        <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">Create Program</h1>
                <p class="text-sm text-slate-600">Add a new program and define its structure and status.</p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('programs.index') }}"
                   class="px-3 py-2 text-xs font-medium rounded-xl bg-white border border-slate-200 text-slate-900 hover:bg-slate-50">
                    Back to Programs
                </a>
            </div>
        </div>
    </div>

    {{-- GLOBAL ERRORS (dashboard style) --}}
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

    {{-- FORM CARD --}}
    <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
        <form action="{{ route('programs.store') }}" method="POST" class="p-6 space-y-6">
            @csrf

            {{-- BASIC DETAILS --}}
            <div>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Program Details</h2>

                    </div>

                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    {{-- Program Code --}}
                    <div>
                        <label for="program_code" class="block text-xs font-medium text-slate-500 mb-1">
                            Program Code <span class="text-rose-600"></span>
                        </label>
                        <input
                            type="text"
                            name="program_code"
                            id="program_code"
                            value="{{ old('program_code') }}"
                            required
                            placeholder="e.g., BSCS"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                                   focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                        />
                        @error('program_code')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Program Name --}}
                    <div>
                        <label for="program_name" class="block text-xs font-medium text-slate-500 mb-1">
                            Program Name <span class="text-rose-600"></span>
                        </label>
                        <input
                            type="text"
                            name="program_name"
                            id="program_name"
                            value="{{ old('program_name') }}"
                            required
                            placeholder="e.g., Bachelor of Science in Computer Science"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                                   focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                        />
                        @error('program_name')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Description --}}
                <div class="mt-4">
                    <label for="description" class="block text-xs font-medium text-slate-500 mb-1">Description</label>
                    <textarea
                        name="description"
                        id="description"
                        rows="4"
                        placeholder="Optional notes about this program…"
                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                               focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                    >{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- SETTINGS --}}
            <div class="pt-2 border-t border-slate-100">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Program Settings</h3>
                        <p class="mt-1 text-xs text-slate-500">Set the program status and structure.</p>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    {{-- Status --}}
                    <div class="sm:col-span-1">
                        <label for="status" class="block text-xs font-medium text-slate-500 mb-1">
                            Status <span class="text-rose-600"></span>
                        </label>
                        <select
                            name="status"
                            id="status"
                            required
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                                   focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                        >
                            <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Duration --}}
                    <div class="sm:col-span-1">
                        <label for="duration" class="block text-xs font-medium text-slate-500 mb-1">
                            Duration (Years) <span class="text-rose-600"></span>
                        </label>
                        <input
                            type="number"
                            name="duration"
                            id="duration"
                            value="{{ old('duration') }}"
                            required
                            min="1"
                            placeholder="e.g., 4"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                                   focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                        />
                        @error('duration')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Terms Per Year --}}
                    <div class="sm:col-span-1">
                        <label for="terms_per_year" class="block text-xs font-medium text-slate-500 mb-1">
                            Terms / Year <span class="text-rose-600"></span>
                        </label>
                        <input
                            type="number"
                            name="terms_per_year"
                            id="terms_per_year"
                            value="{{ old('terms_per_year') }}"
                            required
                            min="1"
                            placeholder="e.g., 2"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                                   focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                        />
                        @error('terms_per_year')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- ACTIONS (dashboard style) --}}
            <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <a href="{{ route('programs.index') }}"
                   class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-medium text-slate-900 hover:bg-slate-50">
                    Cancel
                </a>

                <button type="submit"
                        class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800">
                    Create Program
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
