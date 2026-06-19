{{-- resources/views/admin/approvals/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

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
                <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">Approvals</h1>
                <p class="text-sm text-slate-600">
                    Review pending accounts and approve them to gain system access.
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

    {{-- FILTERS / SEARCH --}}
    <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm">
        <div class="p-4 sm:p-6">
            <form method="GET" action="{{ route('admin.approvals.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3">
                {{-- Search --}}
                <div class="md:col-span-5">
                    <label for="search" class="block text-xs font-medium text-slate-600 mb-1">Search</label>
                    <input
                        type="text"
                        name="search"
                        id="search"
                        value="{{ request('search') }}"
                        placeholder="Name, email, or school ID"
                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm
                               focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                    >
                </div>

                {{-- Role --}}
                <div class="md:col-span-2">
                    <label for="role" class="block text-xs font-medium text-slate-600 mb-1">Role</label>
                    <select
                        name="role"
                        id="role"
                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm
                               focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                    >
                        <option value="all" {{ request('role', 'all') === 'all' ? 'selected' : '' }}>All</option>

                        @if(auth()->user()->hasRole('super_admin'))
                            <option value="teacher" {{ request('role') === 'teacher' ? 'selected' : '' }}>Teacher</option>
                            <option value="program_admin" {{ request('role') === 'program_admin' ? 'selected' : '' }}>Program Admin</option>
                        @endif

                        @if(auth()->user()->hasRole('program_admin'))
                            <option value="student" {{ request('role') === 'student' ? 'selected' : '' }}>Student</option>
                        @endif
                    </select>
                </div>

                {{-- Sort --}}
                <div class="md:col-span-2">
                    <label for="sort" class="block text-xs font-medium text-slate-600 mb-1">Sort</label>
                    <select
                        name="sort"
                        id="sort"
                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm
                               focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                    >
                        <option value="latest" {{ request('sort', 'latest') === 'latest' ? 'selected' : '' }}>Newest</option>
                        <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest</option>
                        <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>Name A-Z</option>
                        <option value="name_desc" {{ request('sort') === 'name_desc' ? 'selected' : '' }}>Name Z-A</option>
                    </select>
                </div>

                {{-- Per page --}}
                <div class="md:col-span-1">
                    <label for="per_page" class="block text-xs font-medium text-slate-600 mb-1">Show</label>
                    <select
                        name="per_page"
                        id="per_page"
                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm
                               focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                    >
                        @foreach([10, 15, 25, 50, 100] as $size)
                            <option value="{{ $size }}" {{ (int) request('per_page', 15) === $size ? 'selected' : '' }}>
                                {{ $size }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Actions --}}
                <div class="md:col-span-2 flex items-end gap-2">
                    <button
                        type="submit"
                        class="flex-1 px-4 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800"
                    >
                        Apply
                    </button>

                    <a
                        href="{{ route('admin.approvals.index') }}"
                        class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-medium text-slate-700 hover:bg-slate-50"
                    >
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- SUPER ADMIN VIEW --}}
    @if(auth()->check() && auth()->user()->hasRole('super_admin'))
        <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
            <div class="px-6 py-4 flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold text-slate-900">Pending Staff</div>
                    <div class="text-xs text-slate-500">Teachers and Program Admins awaiting approval.</div>
                </div>

                <div class="text-xs text-slate-500 text-right">
                    <div>
                        Total on this page:
                        <span class="font-medium text-slate-700">{{ $pendingStaff->count() }}</span>
                    </div>
                    <div>
                        All matched:
                        <span class="font-medium text-slate-700">{{ $pendingStaff->total() }}</span>
                    </div>
                </div>
            </div>

            @if($pendingStaff->isEmpty())
                <div class="px-6 py-12 text-center">
                    <div class="text-sm font-semibold text-slate-900">No pending staff found</div>
                    <div class="mt-1 text-xs text-slate-500">Try changing your search or filters.</div>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs sm:text-sm">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-semibold text-slate-600">
                                <th class="px-6 py-3">Name</th>
                                <th class="px-6 py-3">Email</th>
                                <th class="px-6 py-3">School ID</th>
                                <th class="px-6 py-3">Role</th>
                                <th class="px-6 py-3 text-right">Action</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @foreach($pendingStaff as $user)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-slate-900">
                                            {{ $user->first_name }} {{ $user->last_name }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 text-slate-700">
                                        {{ $user->email }}
                                    </td>

                                    <td class="px-6 py-4 text-slate-700">
                                        {{ $user->school_id ?: '—' }}
                                    </td>

                                    <td class="px-6 py-4">
                                        @php
                                            $roleLabel = $user->roles->pluck('name')->map(function ($r) {
                                                return match ($r) {
                                                    'program_admin' => 'Program Admin',
                                                    'teacher' => 'Teacher',
                                                    'student' => 'Student',
                                                    default => ucfirst(str_replace('_',' ',$r)),
                                                };
                                            })->unique()->values();
                                        @endphp

                                        @if($roleLabel->isEmpty())
                                            <span class="text-xs text-slate-400 italic">—</span>
                                        @else
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($roleLabel as $rl)
                                                    <span class="text-xs px-2 py-1 rounded-full border border-slate-200 bg-slate-50 text-slate-700">
                                                        {{ $rl }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 text-right whitespace-nowrap">
                                        <a href="{{ route('admin.approvals.show', $user) }}"
                                           class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-900 hover:bg-slate-50">
                                            Review
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-slate-100">
                    {{ $pendingStaff->links() }}
                </div>
            @endif
        </div>
    @endif

    {{-- PROGRAM ADMIN VIEW --}}
    @if(auth()->check() && auth()->user()->hasRole('program_admin'))
        <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
            <div class="px-6 py-4 flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold text-slate-900">Pending Students</div>
                    <div class="text-xs text-slate-500">Students awaiting program approval.</div>
                </div>

                <div class="text-xs text-slate-500 text-right">
                    <div>
                        pending accounts total on this page:
                        <span class="font-medium text-slate-700">{{ $pendingStudents->count() }}</span>
                    </div>
                </div>
            </div>

            @if($pendingStudents->isEmpty())
                <div class="px-6 py-12 text-center">
                    <div class="text-sm font-semibold text-slate-900">No pending students found</div>
                    <div class="mt-1 text-xs text-slate-500">Try changing your search or filters.</div>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs sm:text-sm">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-semibold text-slate-600">
                                <th class="px-6 py-3">Name</th>
                                <th class="px-6 py-3">Email</th>
                                <th class="px-6 py-3">School ID</th>
                                <th class="px-6 py-3 text-right">Action</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @foreach($pendingStudents as $user)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-slate-900">
                                            {{ $user->first_name }} {{ $user->last_name }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 text-slate-700">
                                        {{ $user->email }}
                                    </td>

                                    <td class="px-6 py-4 text-slate-700">
                                        {{ $user->school_id ?: '—' }}
                                    </td>

                                    <td class="px-6 py-4 text-right whitespace-nowrap">
                                        <a href="{{ route('admin.approvals.show', $user) }}"
                                           class="inline-flex items-center justify-center px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-900 hover:bg-slate-50">
                                            Review
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-slate-100">
                    {{ $pendingStudents->links() }}
                </div>
            @endif
        </div>
    @endif

</div>
@endsection
