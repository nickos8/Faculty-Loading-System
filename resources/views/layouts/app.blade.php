<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Livewire CSS must be in <head> --}}
    @livewireStyles

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

    <!-- Your app assets -->
    @vite(entrypoints: ['resources/css/app.css', 'resources/js/app.js'])
  </head>

  <body class="font-sans antialiased">
<div class="min-h-screen bg-gradient-to-br from-[#0d83d1] via-[#9eabba] to-[#333f65]">

      @include('layouts.navigation')

      <!-- Page Heading -->
      @isset($header)
        <header class="bg-white shadow">
          <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            {{ $header }}
          </div>
        </header>
      @endisset

      <!-- Page Content -->
      <main>
        @isset($slot)
          {{-- Component usage: <x-app-layout> ... --}}
          {{ $slot }}
        @else
          {{-- Classic usage: @extends('layouts.app') / @section('content') --}}
          @yield('content')
        @endisset
      </main>
    </div>

    {{-- Livewire JS should be just before </body> --}}
    @livewireScripts

    {{-- (optional) a place to push page-specific scripts --}}
    @stack('scripts')
  </body>
</html>
