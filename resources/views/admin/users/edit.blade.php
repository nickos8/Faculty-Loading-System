<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 py-6 space-y-6">

            {{-- HEADER (Subjects style) --}}
            <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
                <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div class="space-y-1">
                        <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">Edit User</h1>
                        <p class="text-sm text-slate-600">
                            {{ $user->last_name }}, {{ $user->first_name }} — {{ $user->email }}
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

            {{-- GLOBAL ERRORS (safe to include) --}}
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
                <form action="{{ route('admin.users.update', $user) }}" method="POST" class="p-6 space-y-6">
                    @csrf
                    @method('PUT')

                    {{-- BASIC INFO --}}
                    <div>
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900">Basic Information</h3>
                                <p class="text-xs text-slate-500 mt-1">Update the user’s name and profile details.</p>
                            </div>
                            <div class="text-xs text-slate-500">
                                Required fields <span class="text-rose-600 font-medium">*</span>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="first_name" class="block text-xs font-medium text-slate-500 mb-1">
                                    First Name <span class="text-rose-600">*</span>
                                </label>
                                <input type="text" name="first_name" id="first_name"
                                       value="{{ old('first_name', $user->first_name) }}"
                                       required
                                       class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm shadow-sm
                                              focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                                @error('first_name')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="last_name" class="block text-xs font-medium text-slate-500 mb-1">
                                    Last Name <span class="text-rose-600">*</span>
                                </label>
                                <input type="text" name="last_name" id="last_name"
                                       value="{{ old('last_name', $user->last_name) }}"
                                       required
                                       class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm shadow-sm
                                              focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                                @error('last_name')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- CONTACT & IDENTITY --}}
                    <div class="pt-2 border-t border-slate-100">
                        <h3 class="text-sm font-semibold text-slate-900">Contact & Identity</h3>

                        <div class="mt-4 grid gap-4 sm:grid-cols-3">
                            <div>
                                <label for="phone_number" class="block text-xs font-medium text-slate-500 mb-1">
                                    Phone Number
                                </label>
                                <input type="text" name="phone_number" id="phone_number"
                                       value="{{ old('phone_number', $user->phone_number) }}"
                                       class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm shadow-sm
                                              focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                                @error('phone_number')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label for="address" class="block text-xs font-medium text-slate-500 mb-1">
                                    Address
                                </label>
                                <input type="text" name="address" id="address"
                                       value="{{ old('address', $user->address) }}"
                                       class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm shadow-sm
                                              focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                                @error('address')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-4 grid gap-4 sm:grid-cols-3">
                            <div>
                                <label for="gender" class="block text-xs font-medium text-slate-500 mb-1">
                                    Gender
                                </label>
                                @php $genderValue = old('gender', $user->gender); @endphp
                                <select name="gender" id="gender"
                                        class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm shadow-sm
                                               focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                                    <option value="">Select</option>
                                    <option value="Male"   @selected($genderValue === 'Male')>Male</option>
                                    <option value="Female" @selected($genderValue === 'Female')>Female</option>
                                    <option value="Other"  @selected($genderValue === 'Other')>Other</option>
                                </select>
                                @error('gender')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="email" class="block text-xs font-medium text-slate-500 mb-1">
                                    Email <span class="text-rose-600">*</span>
                                </label>
                                <input type="email" name="email" id="email"
                                       value="{{ old('email', $user->email) }}"
                                       required
                                       class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm shadow-sm
                                              focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                                @error('email')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="school_id" class="block text-xs font-medium text-slate-500 mb-1">
                                    School ID
                                </label>
                                <input type="text" name="school_id" id="school_id"
                                       value="{{ old('school_id', $user->school_id) }}"
                                       class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm shadow-sm
                                              focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                                @error('school_id')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- PROGRAM & STATUS --}}
                    <div class="pt-2 border-t border-slate-100">
                        <h3 class="text-sm font-semibold text-slate-900">Program & Status</h3>

                        <div class="mt-4 grid gap-4 sm:grid-cols-3">
                            <div>
                                <label for="program_id" class="block text-xs font-medium text-slate-500 mb-1">
                                    Program
                                </label>
                                <select name="program_id" id="program_id"
                                        class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm shadow-sm
                                               focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                                    <option value="">None / Not Assigned</option>
                                    @foreach($programs as $program)
                                        <option value="{{ $program->id }}"
                                            @selected(old('program_id', $user->program_id) == $program->id)>
                                            {{ $program->program_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('program_id')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="status" class="block text-xs font-medium text-slate-500 mb-1">
                                    Status <span class="text-rose-600">*</span>
                                </label>
                                @php $statusValue = old('status', $user->status); @endphp
                                <select name="status" id="status" required
                                        class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm shadow-sm
                                               focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                                    <option value="active"   @selected($statusValue === 'active')>Active</option>
                                    <option value="inactive" @selected($statusValue === 'inactive')>Inactive</option>
                                </select>
                                @error('status')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- ROLES (multi) --}}
                    @php
                        $assigned      = old('roles', $assignedRoles ?? []);
                        $isSuperAdmin  = in_array('super_admin', $assignedRoles ?? [], true);
                    @endphp

                    <div class="pt-2 border-t border-slate-100">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900">Roles</h3>
                                <p class="text-xs text-slate-500 mt-1">Assign one or more roles for access control.</p>
                            </div>
                            <span class="text-xs text-slate-500">Required</span>
                        </div>

                        @if($isSuperAdmin)
                            <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                                This user is a <span class="font-semibold">Super Admin</span>.
                                Role assignments cannot be modified here. Super Admins are managed separately.
                            </div>
                        @else
                            <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                    @foreach($availableRoles as $role)
                                        <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                                            <input
                                                type="checkbox"
                                                name="roles[]"
                                                value="{{ $role->name }}"
                                                class="rounded border-slate-300 text-slate-900 focus:ring-slate-900/10"
                                                @checked(in_array($role->name, $assigned))
                                            >
                                            <span>{{ ucfirst(str_replace('_',' ', $role->name)) }}</span>
                                        </label>
                                    @endforeach
                                </div>

                                <p class="mt-2 text-[11px] text-slate-500">
                                    You may assign multiple roles (e.g., Program Admin + Teacher). At least one role is required.
                                </p>

                                @error('roles')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                                @error('roles.*')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif
                    </div>

                    {{-- ACTIONS --}}
                    <div class="pt-4 border-t border-slate-100 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-[11px] text-slate-500">
                            @php
                                $approver = $user->approvedBy;
                                $approverName = $approver
                                    ? trim(($approver->first_name ?? '').' '.($approver->last_name ?? '')) ?: $approver->email
                                    : null;
                            @endphp

                            Approved by:
                            <span class="font-medium text-slate-700">{{ $approverName ?? '—' }}</span>
                            <span class="text-slate-400">·</span>
                            Approved on:
                            <span class="font-medium text-slate-700">
                                {{ $user->approved_at ? $user->approved_at->format('F d, Y • h:i A') : '—' }}
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.users.index') }}"
                               class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-medium text-slate-900 hover:bg-slate-50">
                                Back
                            </a>

                            <button type="submit"
                                    class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800">
                                Save Changes
                            </button>
                        </div>
                    </div>

                </form>
            </div>

        </div>
    </div>
</x-app-layout>
