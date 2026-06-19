<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 py-6 space-y-6">

            {{-- HEADER (Subjects style) --}}
            <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
                <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div class="space-y-1">
                        <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">
                            Create New User
                        </h1>
                        <p class="text-sm text-slate-600">
                            Create a Program Admin, Teacher, or Student account.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.users.index') }}"
                           class="px-3 py-2 text-xs font-medium rounded-xl bg-white border border-slate-200 text-slate-900 hover:bg-slate-50">
                            Back to Users
                        </a>
                    </div>
                </div>
            </div>

            {{-- GLOBAL ERRORS --}}
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
                <form action="{{ route('admin.users.store') }}" method="POST" class="p-6 space-y-6">
                    @csrf

                    {{-- TIP --}}
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                        <span class="font-semibold text-slate-900">Tip:</span>
                        Initial passwords are generated automatically. You may show or email them after creation.
                    </div>

                    {{-- BASIC INFORMATION --}}
                    <div>
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900">Basic Information</h3>
                                <p class="text-xs text-slate-500 mt-1">Personal and contact details.</p>
                            </div>
                            <div class="text-xs text-slate-500">
                                Required fields <span class="text-rose-600 font-medium">*</span>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">
                                    First Name <span class="text-rose-600">*</span>
                                </label>
                                <input type="text" name="first_name"
                                       value="{{ old('first_name') }}" required
                                       class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm shadow-sm
                                              focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                                @error('first_name')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">
                                    Last Name <span class="text-rose-600">*</span>
                                </label>
                                <input type="text" name="last_name"
                                       value="{{ old('last_name') }}" required
                                       class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm shadow-sm
                                              focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                                @error('last_name')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- CONTACT --}}
                    <div class="pt-2 border-t border-slate-100">
                        <h3 class="text-sm font-semibold text-slate-900">Contact & Identity</h3>

                        <div class="mt-4 grid gap-4 sm:grid-cols-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Phone Number</label>
                                <input type="text" name="phone_number"
                                       value="{{ old('phone_number') }}"
                                       class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm shadow-sm">
                                @error('phone_number')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-slate-500 mb-1">Address</label>
                                <input type="text" name="address"
                                       value="{{ old('address') }}"
                                       class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm shadow-sm">
                                @error('address')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-4 grid gap-4 sm:grid-cols-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Gender</label>
                                <select name="gender"
                                        class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm shadow-sm">
                                    <option value="">Select</option>
                                    @foreach(['Male','Female','Other'] as $g)
                                        <option value="{{ $g }}" @selected(old('gender') === $g)>{{ $g }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">
                                    Email <span class="text-rose-600">*</span>
                                </label>
                                <input type="email" name="email"
                                       value="{{ old('email') }}" required
                                       class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm shadow-sm">
                                @error('email')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">School ID</label>
                                <input type="text" name="school_id"
                                       value="{{ old('school_id') }}"
                                       class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm shadow-sm">
                                @error('school_id')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- ROLE & ACCESS --}}
                    <div class="pt-2 border-t border-slate-100">
                        <h3 class="text-sm font-semibold text-slate-900">Role & Access</h3>

                        <div class="mt-4 grid gap-4 sm:grid-cols-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Program</label>
                                <select name="program_id"
                                        class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm shadow-sm">
                                    <option value="">None / Not Assigned</option>
                                    @foreach($programs as $program)
                                        <option value="{{ $program->id }}" @selected(old('program_id') == $program->id)>
                                            {{ $program->program_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">
                                    Role <span class="text-rose-600">*</span>
                                </label>
                                <select name="role" required
                                        class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm shadow-sm">
                                    <option value="">Select role</option>
                                    <option value="program_admin" @selected(old('role')==='program_admin')>Program Admin</option>
                                    <option value="teacher" @selected(old('role')==='teacher')>Teacher</option>
                                    <option value="student" @selected(old('role')==='student')>Student</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
                                @php $currentStatus = old('status','active'); @endphp
                                <select name="status"
                                        class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm shadow-sm">
                                    <option value="active" @selected($currentStatus==='active')>Active</option>
                                    <option value="inactive" @selected($currentStatus==='inactive')>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- ACTIONS --}}
                    <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <a href="{{ route('admin.users.index') }}"
                           class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-medium text-slate-900 hover:bg-slate-50">
                            Cancel
                        </a>

                        <button type="submit"
                                class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800">
                            Save User
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
