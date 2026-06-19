{{-- resources/views/program-admin/students/create.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6 space-y-6">

    {{-- HEADER (dashboard style) --}}
    <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
        <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">Create Student</h1>
                <p class="text-sm text-slate-600">Add a new student to your program.</p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('program-admin.students.index') }}"
                   class="px-3 py-2 text-xs font-medium rounded-xl bg-white border border-slate-200 text-slate-900 hover:bg-slate-50">
                    ← Back to students
                </a>
            </div>
        </div>
    </div>

    {{-- GLOBAL ERRORS / SUCCESS (dashboard style) --}}
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

    @if (session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 shadow-sm">
            <div class="font-semibold">Success</div>
            <div class="text-sm mt-1">{{ session('success') }}</div>
        </div>
    @endif

    {{-- FORM CARD --}}
    <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
        <form action="{{ route('program-admin.students.store') }}" method="POST" class="p-6 space-y-6">
            @csrf

            {{-- TIP / CONTEXT --}}
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                <span class="font-semibold text-slate-900">Note:</span>
                Program is locked here. You can transfer a student later from the Edit page.
            </div>

            {{-- PROGRAM (READ-ONLY) --}}
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Program</label>
                <input type="text"
                       value="{{ $program->program_name }}"
                       disabled
                       class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-700 shadow-sm">
            </div>

            {{-- BASIC INFORMATION --}}
            <div class="pt-2 border-t border-slate-100">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Basic Information</h2>
                        <p class="text-xs text-slate-500 mt-1">Student profile details.</p>
                    </div>
                    <div class="text-xs text-slate-500">
                        Fields marked <span class="text-rose-600 font-medium">*</span> are required
                    </div>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">
                            First Name <span class="text-rose-600">*</span>
                        </label>
                        <input type="text"
                               name="first_name"
                               required
                               value="{{ old('first_name') }}"
                               class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                                      focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">
                            Last Name <span class="text-rose-600">*</span>
                        </label>
                        <input type="text"
                               name="last_name"
                               required
                               value="{{ old('last_name') }}"
                               class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                                      focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-xs font-medium text-slate-500 mb-1">
                        Phone Number <span class="text-rose-600">*</span>
                    </label>
                    <input type="text"
                           name="phone_number"
                           required
                           value="{{ old('phone_number') }}"
                           class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                                  focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                </div>

                <div class="mt-4">
                    <label class="block text-xs font-medium text-slate-500 mb-1">
                        Address <span class="text-rose-600">*</span>
                    </label>
                    <textarea name="address"
                              required
                              rows="3"
                              class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                                     focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300 resize-y">{{ old('address') }}</textarea>
                </div>

                <div class="mt-4">
                    <label class="block text-xs font-medium text-slate-500 mb-1">
                        Gender <span class="text-rose-600">*</span>
                    </label>
                    <select name="gender"
                            required
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm
                                   focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                        <option value="">-- Select --</option>
                        @foreach (['Male','Female','Other'] as $gender)
                            <option value="{{ $gender }}" @selected(old('gender') === $gender)>
                                {{ $gender }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- ACCOUNT INFORMATION --}}
            <div class="pt-2 border-t border-slate-100">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Account Information</h2>
                        <p class="text-xs text-slate-500 mt-1">Credentials used for login.</p>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">
                            School ID <span class="text-rose-600">*</span>
                        </label>
                        <input type="text"
                               name="school_id"
                               required
                               value="{{ old('school_id') }}"
                               class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                                      focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">
                            Email <span class="text-rose-600">*</span>
                        </label>
                        <input type="email"
                               name="email"
                               required
                               value="{{ old('email') }}"
                               class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                                      focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                    </div>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">
                            Password <span class="text-rose-600">*</span>
                        </label>
                        <input type="password"
                               name="password"
                               required
                               class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                                      focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">
                            Confirm Password <span class="text-rose-600">*</span>
                        </label>
                        <input type="password"
                               name="password_confirmation"
                               required
                               class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                                      focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                    </div>
                </div>
            </div>

            {{-- ACADEMIC INFORMATION --}}
            <div class="pt-2 border-t border-slate-100">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Academic Information</h2>
                        <p class="text-xs text-slate-500 mt-1">Optional section assignment and academic status.</p>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Section (optional)</label>
                    <select name="section_id"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm
                                   focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                        <option value="">-- No section yet --</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}" @selected(old('section_id') == $section->id)>
                                {{ $section->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox"
                               name="leave_curriculum_empty"
                               value="1"
                               class="h-4 w-4 rounded border-slate-300"
                               {{ old('leave_curriculum_empty') ? 'checked' : '' }}>
                        <span class="text-sm text-slate-700">
                            Leave curriculum empty (don’t auto-assign)
                        </span>
                    </label>
                    <p class="mt-2 text-xs text-slate-500">
                        Enable this if you want to assign the curriculum later (or manage it manually).
                    </p>
                </div>

                <div class="mt-4">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Academic Status</label>
                    <select name="academic_status"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm
                                   focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                        <option value="regular" @selected(old('academic_status','regular') === 'regular')>Regular</option>
                        <option value="irregular" @selected(old('academic_status') === 'irregular')>Irregular</option>
                    </select>
                </div>
            </div>

            {{-- ACTIONS (dashboard style) --}}
            <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <a href="{{ route('program-admin.students.index') }}"
                   class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-medium text-slate-900 hover:bg-slate-50">
                    Cancel
                </a>

                <button type="submit"
                        class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800">
                    Create Student
                </button>
            </div>

        </form>
    </div>
</div>
@endsection
