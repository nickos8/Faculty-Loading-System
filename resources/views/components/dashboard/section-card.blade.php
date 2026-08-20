@props([
    'title',
])

<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-4 flex items-center gap-3 border-b border-slate-100 pb-3">
        <span class="h-6 w-1 rounded-full bg-gradient-to-b from-[#0b6fb8] to-[#54c3e8]"></span>
        <h2 class="text-sm font-bold text-slate-800">{{ $title }}</h2>
    </div>
    {{ $slot }}
</div>
