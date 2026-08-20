<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Granby Faculty Loading System') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <main class="gc-auth-shell">
            <div class="gc-auth-card">
                <div class="mb-7 flex flex-col items-center text-center">
                    <img src="{{ asset('images/granbylogo.jpg') }}" alt="Granby Colleges logo" class="gc-auth-logo mb-4">
                    <h1 class="text-2xl font-extrabold tracking-tight text-[#0b3158]">Granby Colleges</h1>
                    <p class="mt-1 text-sm font-semibold text-[#0b6fb8]">Faculty Loading & Scheduling System</p>
                </div>
                {{ $slot }}
            </div>
        </main>
    </body>
</html>
