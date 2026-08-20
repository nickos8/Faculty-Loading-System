@php
    $currentUser = Auth::user();
    $roleLabel = match (true) {
        $currentUser->hasRole('super_admin') => 'Super Admin',
        $currentUser->hasRole('program_admin') => 'Program Admin',
        $currentUser->hasRole('teacher') => 'Teacher',
        $currentUser->hasRole('student') => 'Student',
        default => 'User',
    };
    $initials = strtoupper(substr($currentUser->first_name, 0, 1) . substr($currentUser->last_name, 0, 1));
@endphp

<nav
    x-data="{
        sidebarOpen: false,
        init() {
            this.$watch('sidebarOpen', value => document.body.classList.toggle('overflow-hidden', value));
        }
    }"
    class="gc-topbar sticky top-0 z-50"
>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-[4.5rem] items-center gap-3">
            <button
                type="button"
                class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-400"
                @click="sidebarOpen = true"
                aria-label="Open navigation"
                :aria-expanded="sidebarOpen.toString()"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-sky-400">
                <img src="{{ asset('images/granbylogo.jpg') }}" alt="Granby Colleges logo" class="gc-brand-mark h-10 w-10 rounded-xl object-cover">
                <span class="min-w-0">
                    <span class="block truncate text-base font-extrabold tracking-tight text-[#0b3158] sm:text-lg">Granby Colleges</span>
                    <span class="hidden text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-500 sm:block">Faculty Loading System</span>
                </span>
            </a>

            <div class="ml-auto flex items-center gap-2">
                <span class="gc-role-badge hidden md:inline-flex">{{ $roleLabel }}</span>

                <x-dropdown align="right" width="56">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 rounded-xl border border-transparent px-2 py-1.5 text-sm font-semibold text-slate-700 hover:border-slate-200 hover:bg-white focus:outline-none focus:ring-2 focus:ring-sky-400 sm:px-3">
                            <span class="gc-avatar">{{ $initials }}</span>
                            <span class="hidden max-w-40 truncate sm:inline">{{ $currentUser->first_name }} {{ $currentUser->last_name }}</span>
                            <svg class="h-4 w-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="border-b border-slate-100 px-4 py-3">
                            <p class="truncate text-sm font-bold text-slate-800">{{ $currentUser->first_name }} {{ $currentUser->last_name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $currentUser->email }}</p>
                        </div>
                        <x-dropdown-link :href="route('profile.edit')">Profile settings</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Log out</x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </div>

    <div x-show="sidebarOpen" x-cloak @keydown.escape.window="sidebarOpen = false" class="fixed inset-0 z-50 h-[100dvh] overflow-hidden">
        <div
            class="absolute inset-0 bg-slate-950/50 backdrop-blur-[2px]"
            x-transition:enter="transition-opacity ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="sidebarOpen = false"
        ></div>

        <aside
            class="gc-drawer absolute inset-y-0 left-0 flex h-[100dvh] w-80 max-w-[88vw] flex-col overflow-hidden"
            x-transition:enter="transform transition ease-out duration-300"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            role="dialog"
            aria-modal="true"
            aria-label="Main navigation"
        >
            <div class="flex h-[4.5rem] shrink-0 items-center justify-between border-b border-slate-200 px-5">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/granbylogo.jpg') }}" alt="" class="gc-brand-mark h-10 w-10 rounded-xl object-cover">
                    <div>
                        <p class="text-sm font-extrabold text-[#0b3158]">Navigation</p>
                        <p class="text-xs text-slate-500">{{ $roleLabel }} portal</p>
                    </div>
                </div>
                <button type="button" class="rounded-xl p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-400" @click="sidebarOpen = false" aria-label="Close menu">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-3 py-3">
                @include('partials._role_sidebar_links')
            </div>

            <div class="shrink-0 border-t border-slate-200 bg-white/70 p-4">
                <div class="flex items-center gap-3 rounded-xl bg-slate-50 p-3">
                    <span class="gc-avatar">{{ $initials }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold text-slate-800">{{ $currentUser->first_name }} {{ $currentUser->last_name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ $currentUser->email }}</p>
                    </div>
                </div>
                <div class="mt-2 grid grid-cols-2 gap-2">
                    <a href="{{ route('profile.edit') }}" @click="sidebarOpen=false" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-center text-xs font-semibold text-slate-700 hover:bg-slate-50">Profile</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Log out</button>
                    </form>
                </div>
            </div>
        </aside>
    </div>
</nav>
