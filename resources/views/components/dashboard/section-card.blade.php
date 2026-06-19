@props([
    'title',
])

<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="text-sm font-semibold text-slate-800 mb-4">{{ $title }}</h2>
    {{ $slot }}
</div>
