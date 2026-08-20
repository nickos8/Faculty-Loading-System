<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Granby Faculty Loading & Scheduling System</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-[radial-gradient(circle_at_10%_10%,rgba(84,195,232,0.28),transparent_25rem),linear-gradient(135deg,#06294b_0%,#0b558b_52%,#0b6fb8_100%)] text-white">
            <header class="mx-auto flex max-w-7xl items-center justify-between px-5 py-5 sm:px-8 lg:px-10">
                <a href="{{ url('/') }}" class="flex items-center gap-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-white/70">
                    <img src="{{ asset('images/granbylogo.jpg') }}" alt="Granby Colleges logo" class="h-11 w-11 rounded-xl object-cover shadow-lg ring-1 ring-white/30">
                    <span>
                        <span class="block text-base font-extrabold tracking-tight sm:text-lg">Granby Colleges</span>
                        <span class="hidden text-[10px] font-bold uppercase tracking-[0.14em] text-sky-100 sm:block">Faculty Loading System</span>
                    </span>
                </a>

                <nav class="flex items-center gap-2" aria-label="Account navigation">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-[#0b3158] shadow-lg hover:bg-sky-50">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-xl border border-white/30 bg-white/10 px-4 py-2.5 text-sm font-bold text-white backdrop-blur hover:bg-white/20">Log in</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="hidden rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-[#0b3158] shadow-lg hover:bg-sky-50 sm:inline-flex">Create account</a>
                        @endif
                    @endauth
                </nav>
            </header>

            <main class="mx-auto grid max-w-7xl items-center gap-12 px-5 pb-14 pt-10 sm:px-8 sm:pt-16 lg:grid-cols-[1.05fr_.95fr] lg:px-10 lg:pb-24 lg:pt-20">
                <section>
                    <span class="inline-flex rounded-full border border-sky-200/30 bg-sky-100/10 px-3 py-1 text-xs font-bold uppercase tracking-[0.14em] text-sky-100 backdrop-blur">Academic scheduling, simplified</span>
                    <h1 class="mt-6 max-w-3xl text-4xl font-extrabold leading-tight tracking-tight sm:text-5xl lg:text-6xl">
                        One connected system for faculty loads and student schedules.
                    </h1>
                    <p class="mt-6 max-w-2xl text-base leading-7 text-sky-50/85 sm:text-lg">
                        Plan class offerings, manage faculty availability, prevent schedule conflicts, and give every role a clear view of the academic term.
                    </p>
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-extrabold text-[#0b3158] shadow-xl hover:-translate-y-0.5 hover:bg-sky-50">
                                Open dashboard <span aria-hidden="true">→</span>
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-extrabold text-[#0b3158] shadow-xl hover:-translate-y-0.5 hover:bg-sky-50">
                                Access the system <span aria-hidden="true">→</span>
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-xl border border-white/30 bg-white/10 px-5 py-3 text-sm font-bold text-white backdrop-blur hover:bg-white/20">Register an account</a>
                            @endif
                        @endauth
                    </div>
                </section>

                <section class="relative" aria-label="System capabilities">
                    <div class="absolute -inset-4 rounded-[2rem] bg-sky-300/10 blur-2xl"></div>
                    <div class="relative overflow-hidden rounded-3xl border border-white/25 bg-white/95 p-5 text-[#132238] shadow-2xl backdrop-blur sm:p-7">
                        <div class="flex items-center justify-between border-b border-slate-200 pb-5">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.12em] text-[#0b6fb8]">System overview</p>
                                <h2 class="mt-1 text-xl font-extrabold text-[#0b3158]">Built for every academic role</h2>
                            </div>
                            <img src="{{ asset('images/granbylogo.jpg') }}" alt="" class="h-12 w-12 rounded-xl object-cover shadow-md">
                        </div>

                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            @foreach ([
                                ['Faculty Loading', 'Availability, preferences, and teaching loads'],
                                ['Smart Scheduling', 'Conflict-aware rooms, sections, and meetings'],
                                ['Student Enrollment', 'Curriculum, prerequisites, and current subjects'],
                                ['Reports & Evaluation', 'Finalized schedules and printable records'],
                            ] as [$title, $description])
                                <div class="rounded-2xl border border-slate-200 bg-[#f7fbfe] p-4">
                                    <span class="mb-3 block h-2 w-10 rounded-full bg-gradient-to-r from-[#0b3158] to-[#54c3e8]"></span>
                                    <h3 class="text-sm font-extrabold text-slate-900">{{ $title }}</h3>
                                    <p class="mt-1 text-xs leading-5 text-slate-600">{{ $description }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            </main>

            <footer class="border-t border-white/10 px-5 py-5 text-center text-xs text-sky-100/70">
                Granby Colleges of Science and Technology · Faculty Loading & Scheduling System
            </footer>
        </div>
    </body>
</html>
