@props([
    'label',
    'value',
    'hint' => null,
    'href' => null,
])

@if($href)
    <a href="{{ $href }}"
       class="group relative block overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:-translate-y-0.5 hover:border-sky-200 hover:shadow-lg transition">
@else
    <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
@endif

        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-[#0b3158] via-[#0b6fb8] to-[#54c3e8]"></div>
        <div class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ $label }}</div>

        <div class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900">
            {{ $value }}
        </div>

        @if($hint)
            <div class="mt-2 text-xs text-slate-500">{{ $hint }}</div>
        @endif

        @if($href)
            <div class="mt-4 flex items-center justify-between">
                <span class="inline-flex items-center gap-1 rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white group-hover:bg-slate-800 transition">
                    View details <span aria-hidden="true">→</span>
                </span>
            </div>
        @endif

@if($href)
    </a>
@else
    </div>
@endif
