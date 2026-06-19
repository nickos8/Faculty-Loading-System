<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

            {{-- HEADER (Subjects style) --}}
            <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
                <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div class="space-y-1">
                        <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">User Management</h1>
                        <p class="text-sm text-slate-600">Manage program admins, teachers, and students.</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.users.create') }}"
                           class="px-3 py-2 text-xs font-medium rounded-xl bg-slate-900 text-white hover:bg-slate-800">
                            Create Account
                        </a>
                    </div>
                </div>
            </div>

            {{-- FLASH / ERRORS (optional; safe to keep even if not used) --}}
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

            {{-- FILTERS (Subjects style) --}}
            <div class="rounded-2xl border border-slate-200/70 bg-white p-6 shadow-sm">
                <form method="GET" class="grid gap-4 sm:grid-cols-12">
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-medium text-slate-500 mb-1">Program</label>
                        <select name="program_id"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                                       focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                            <option value="">All programs</option>
                            @foreach($programs as $p)
                                <option value="{{ $p->id }}" @selected($programId == $p->id)>
                                    {{ $p->program_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
                        <select name="status"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                                       focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                            @foreach(['any' => 'Any', 'active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
                                <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-5">
                        <label class="block text-xs font-medium text-slate-500 mb-1">Search</label>
                        <input type="text"
                               name="search"
                               value="{{ $search }}"
                               placeholder="Name, email, or school ID…"
                               class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                                      focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300">
                    </div>

                    <div class="sm:col-span-2 flex items-end gap-2">
                        <button type="submit"
                                class="w-full sm:w-auto px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800">
                            Apply
                        </button>

                        @if(!empty($search) || !empty($programId) || ($status ?? 'any') !== 'any')
                            <a href="{{ route('admin.users.index') }}"
                               class="w-full sm:w-auto px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-medium text-slate-900 hover:bg-slate-50">
                                Clear
                            </a>
                        @endif
                    </div>
                </form>

                <div class="mt-3 text-xs text-slate-500">
                    Showing <span class="font-medium text-slate-700">{{ $users->count() }}</span> user(s)
                    @if(!empty($search))
                        for <span class="font-medium text-slate-700">“{{ $search }}”</span>
                    @endif
                </div>
            </div>

            {{-- CONTENT --}}
            @if($users->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center shadow-sm">
                    <div class="text-sm font-semibold text-slate-900">No users found</div>
                    <div class="mt-1 text-xs text-slate-500">Try adjusting your filters or create a new account.</div>

                    <div class="mt-5">
                        <a href="{{ route('admin.users.create') }}"
                           class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800">
                            + Create Account
                        </a>
                    </div>
                </div>
            @else
                <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
                    <div class="px-6 py-4 flex items-center justify-between">
                        <div>
                            <div class="text-sm font-semibold text-slate-900">Users</div>
                            <div class="text-xs text-slate-500">Edit users or toggle active status.</div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-xs sm:text-sm">
                            <thead class="bg-slate-50">
                                <tr class="text-left text-xs font-semibold text-slate-600">
                                    <th class="px-6 py-3">Name</th>
                                    <th class="px-6 py-3">Email</th>
                                    <th class="px-6 py-3">School ID</th>
                                    <th class="px-6 py-3">Program</th>
                                    <th class="px-6 py-3">Role</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100">
                                @forelse($users as $u)
                                    @php
                                        $roleName = $u->roles->pluck('name')->first();
                                    @endphp

                                    <tr class="hover:bg-slate-50/60">
                                        <td class="px-6 py-4">
                                            <div class="font-medium text-slate-900">
                                                {{ $u->last_name }}, {{ $u->first_name }}
                                            </div>
                                        </td>

                                        <td class="px-6 py-4 text-slate-700">
                                            {{ $u->email }}
                                        </td>

                                        <td class="px-6 py-4 text-slate-700">
                                            {{ $u->school_id ?? '—' }}
                                        </td>

                                        <td class="px-6 py-4 text-slate-700">
                                            {{ $u->program->program_code ?? '—' }}
                                        </td>

                                        <td class="px-6 py-4 text-slate-700 capitalize">
                                            {{ str_replace('_',' ', $roleName ?? '—') }}
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium border
                                                @class([
                                                    'border-emerald-200 bg-emerald-50 text-emerald-700' => $u->status === 'active',
                                                    'border-slate-200 bg-slate-50 text-slate-700'       => $u->status === 'inactive',
                                                    'border-amber-200 bg-amber-50 text-amber-700'       => $u->status === 'pending',
                                                    'border-rose-200 bg-rose-50 text-rose-700'          => $u->status === 'declined',
                                                ])">
                                                {{ ucfirst($u->status) }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4 text-right whitespace-nowrap">
                                            <div class="inline-flex items-center gap-2">
                                                <a href="{{ route('admin.users.edit', $u) }}"
                                                   class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-900 hover:bg-slate-50">
                                                    Edit
                                                </a>

                                                <form action="{{ route('admin.users.status', $u) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="status" value="{{ $u->status === 'active' ? 'inactive' : 'active' }}">

                                                    <button type="submit"
                                                            class="inline-flex items-center justify-center px-3 py-2 rounded-xl border text-xs font-medium
                                                                {{ $u->status === 'active'
                                                                    ? 'border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100'
                                                                    : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                                                        {{ $u->status === 'active' ? 'Deactivate' : 'Activate' }}
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-8 text-center text-sm text-slate-500">
                                            No users found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="px-6 py-4 border-t border-slate-100">
                        {{ $users->links() }}
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
