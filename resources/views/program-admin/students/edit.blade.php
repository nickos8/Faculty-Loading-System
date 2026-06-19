{{-- resources/views/program-admin/students/edit.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6 space-y-6">

    {{-- HEADER (dashboard style) --}}
    <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
        <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">Edit Student</h1>

                <div class="text-sm text-slate-600">
                    <span class="font-medium text-slate-900">{{ $student->first_name }} {{ $student->last_name }}</span>
                    <span class="mx-2 text-slate-300">•</span>
                    <span class="text-slate-500">School ID:</span>
                    <span class="font-medium text-slate-900">{{ $student->school_id }}</span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ url()->previous() }}"
                   class="px-3 py-2 text-xs font-medium rounded-xl bg-white border border-slate-200 text-slate-900 hover:bg-slate-50">
                    ← Back
                </a>
            </div>
        </div>
    </div>

    {{-- Specific availability error --}}
    @if ($errors->has('availability'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-800 shadow-sm">
            <div class="font-semibold">Notice</div>
            <div class="text-sm mt-1">{{ $errors->first('availability') }}</div>
        </div>
    @endif

    {{-- Flash + Errors (dashboard style) --}}
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
        <div class="px-6 py-4 flex items-center justify-between">
            <div>
                <div class="text-sm font-semibold text-slate-900">Student Details</div>
                <div class="text-xs text-slate-500">Update personal, account, and academic information.</div>
            </div>

            <div class="text-xs text-slate-500">
                Program: <span class="font-medium text-slate-700">{{ $program->program_name }}</span>
            </div>
        </div>

        <form action="{{ route('program-admin.students.update', $student->id) }}"
              method="POST"
              class="p-6 space-y-6">
            @csrf
            @method('PUT')

            {{-- PROGRAM (TRANSFER) --}}
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Program</h2>
                        <p class="text-xs text-slate-500 mt-1">
                            Changing this transfers the student to another program. Their current section will be cleared.
                        </p>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Program</label>
                    <select name="program_id"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm
                                   focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                        @foreach($programs as $prog)
                            <option value="{{ $prog->id }}"
                                @selected(old('program_id', $student->program_id) == $prog->id)>
                                {{ $prog->program_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- BASIC INFORMATION --}}
            <div class="pt-2 border-t border-slate-100">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Basic Information</h2>
                        <p class="text-xs text-slate-500 mt-1">Keep names and contact details up to date.</p>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">First Name <span class="text-rose-600">*</span></label>
                        <input type="text"
                               name="first_name"
                               value="{{ old('first_name', $student->first_name) }}"
                               required
                               class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                                      focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Last Name <span class="text-rose-600">*</span></label>
                        <input type="text"
                               name="last_name"
                               value="{{ old('last_name', $student->last_name) }}"
                               required
                               class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                                      focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Phone Number <span class="text-rose-600">*</span></label>
                    <input type="text"
                           name="phone_number"
                           value="{{ old('phone_number', $student->phone_number) }}"
                           required
                           class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                                  focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                </div>

                <div class="mt-4">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Address <span class="text-rose-600">*</span></label>
                    <textarea name="address"
                              required
                              rows="3"
                              class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                                     focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300 resize-y">{{ old('address', $student->address) }}</textarea>
                </div>

                <div class="mt-4">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Gender <span class="text-rose-600">*</span></label>
                    <select name="gender"
                            required
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm
                                   focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                        <option value="">Select</option>
                        @foreach (['Male','Female'] as $gender)
                            <option value="{{ $gender }}" @selected(old('gender', $student->gender) === $gender)>
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
                        <p class="text-xs text-slate-500 mt-1">Credentials and account status.</p>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">School ID <span class="text-rose-600">*</span></label>
                        <input type="text"
                               name="school_id"
                               value="{{ old('school_id', $student->school_id) }}"
                               required
                               class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                                      focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Email <span class="text-rose-600">*</span></label>
                        <input type="email"
                               name="email"
                               value="{{ old('email', $student->email) }}"
                               required
                               class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                                      focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Account Status <span class="text-rose-600">*</span></label>
                    <select name="status"
                            required
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm
                                   focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                        @foreach (['active','inactive'] as $status)
                            <option value="{{ $status }}" @selected(old('status', $student->status) === $status)>
                                {{ ucfirst($status) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- APPROVAL INFO (READ ONLY) --}}
                @php
                    $approver      = $student->approvedBy;
                    $approvedByStr = 'Not yet approved';
                    $approvedAtStr = '—';

                    if ($approver) {
                        $nameParts = trim(($approver->first_name ?? '').' '.($approver->last_name ?? ''));
                        $approvedByStr = $nameParts !== ''
                            ? $nameParts
                            : ($approver->name ?? $approver->email ?? 'Unknown');

                        if ($student->approved_at) {
                            $approvedAtStr = $student->approved_at->format('Y-m-d H:i');
                        }
                    }
                @endphp

                @if($approver || $student->approved_at)
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Approved By</label>
                            <input type="text"
                                   value="{{ $approvedByStr }}"
                                   readonly
                                   class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-700 shadow-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Approved At</label>
                            <input type="text"
                                   value="{{ $approvedAtStr }}"
                                   readonly
                                   class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-700 shadow-sm">
                        </div>
                    </div>
                @else
                    <div class="mt-4 text-xs text-slate-500">
                        This account has not been approved yet.
                    </div>
                @endif
            </div>

            {{-- ACADEMIC INFORMATION --}}
            <div class="pt-2 border-t border-slate-100">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Academic Information</h2>
                        <p class="text-xs text-slate-500 mt-1">Section placement and enrollment status.</p>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Section (optional)</label>
                    <select name="section_id"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm
                                   focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                        <option value="">-- No section --</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}"
                                @selected(old('section_id', $academic->section_id) == $section->id)>
                                {{ $section->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Academic Status <span class="text-rose-600">*</span></label>
                        <select name="academic_status"
                                required
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm
                                       focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                            <option value="regular" @selected(old('academic_status', $academic->status) === 'regular')>Regular</option>
                            <option value="irregular" @selected(old('academic_status', $academic->status) === 'irregular')>Irregular</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Enrollment Status <span class="text-rose-600">*</span></label>
                        <select name="enrollment_status"
                                required
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm
                                       focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                            @foreach (['enrolled','dropped','graduated'] as $enrollment)
                                <option value="{{ $enrollment }}"
                                    @selected(old('enrollment_status', $academic->enrollment_status) === $enrollment)>
                                    {{ ucfirst($enrollment) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- ACTIONS (dashboard style) --}}
            <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <a href="{{ url()->previous() }}"
                   class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-medium text-slate-900 hover:bg-slate-50">
                    Cancel
                </a>

                <button type="submit"
                        class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800">
                    Save Changes
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
