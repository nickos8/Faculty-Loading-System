<nav
  x-data="{
    sidebarOpen: false,
    init() {
      this.$watch('sidebarOpen', (value) => {
        document.body.classList.toggle('overflow-hidden', value);
      });
    }
  }"
  class="relative z-50 bg-white border-b border-gray-200"
>
    {{-- TOP BAR --}}
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center gap-4">

            {{-- Toggle Button (desktop + mobile) --}}
            <button
                type="button"
                class="inline-flex items-center justify-center rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                @click="sidebarOpen = true"
                aria-label="Open navigation"
                :aria-expanded="sidebarOpen.toString()"
            >
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            {{-- Brand --}}
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <img src="{{ asset('images/granbylogo.jpg') }}" alt="Logo" class="h-9 w-auto rounded">
                    <span class="text-lg font-semibold text-gray-800">Granby Colleges</span>
                </a>
            </div>

            {{-- Center text (desktop) --}}
            <div class="hidden md:flex flex-1 items-center justify-center">

            </div>

            {{-- User dropdown --}}
            <div class="ml-auto flex items-center">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button
                            class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <span class="hidden sm:inline">
                                {{ Auth::user()->first_name }} {{ Auth::user()->last_name }}
                            </span>
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd"
                                      d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                      clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link
                                :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </div>

    {{-- DRAWER (works on desktop + mobile) --}}
    <div
    class="fixed inset-y-0 left-0 w-80 max-w-[85vw] bg-white shadow-xl z-50 flex flex-col"
    x-transition:enter="transform transition ease-out duration-300 delay-100"
    x-transition:enter-start="-translate-x-full"
    x-transition:enter-end="translate-x-0"
    x-transition:leave="transform transition ease-in duration-200"
    x-transition:leave-start="translate-x-0"
    x-transition:leave-end="-translate-x-full"
        x-show="sidebarOpen"
        x-cloak
        @keydown.escape.window="sidebarOpen = false"
        style="display: none;"
    >
        {{-- Backdrop --}}
        <div

           class="fixed inset-0 bg-black/40 z-40"
    x-transition:enter="transition-opacity ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @click="sidebarOpen = false"
        ></div>

        {{-- Panel --}}
        <div
            class="fixed inset-y-0 left-0 w-80 max-w-[85vw] bg-white shadow-xl z-50 flex flex-col"
            x-transition:enter="transform transition ease-out duration-200"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in duration-150"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
        >
            {{-- Header --}}
            <div class="flex items-center justify-between px-4 h-16 border-b border-gray-200 shrink-0">
                <div class="flex items-center gap-2">
                    <img src="{{ asset('images/granbylogo.jpg') }}" alt="Logo" class="h-8 w-auto rounded">
                    <span class="font-semibold text-gray-800">Menu</span>
                </div>

                <button
                    type="button"
                    class="rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    @click="sidebarOpen=false"
                    aria-label="Close menu"
                >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Links --}}
            <div class="p-2 overflow-y-auto flex-1">
                @include('partials._role_sidebar_links')
            </div>

            {{-- Footer --}}
            <div class="border-t border-gray-200 p-4 shrink-0">
                <div class="text-sm font-medium text-gray-800">
                    {{ Auth::user()->first_name }} {{ Auth::user()->last_name }}
                </div>
                <div class="text-sm text-gray-500">
                    {{ Auth::user()->email }}
                </div>

                <div class="mt-3 space-y-1">
                    <a href="{{ route('profile.edit') }}"
                       @click="sidebarOpen=false"
                       class="block rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
                        Profile
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="w-full text-left rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
                            Log Out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</nav>
