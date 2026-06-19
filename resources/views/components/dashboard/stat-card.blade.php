@props([
    'label',
    'value',
    'hint' => null,
    'href' => null,
])

@if($href)
    <a href="{{ $href }}"
       class="group block rounded-2xl border border-slate-200 bg-white p-4 shadow-sm hover:border-slate-300 hover:bg-slate-50 transition">
@else
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
@endif

        <div class="text-sm text-slate-500">{{ $label }}</div>

        <div class="mt-2 text-3xl font-bold text-slate-900">
            {{ $value }}
        </div>

        @if($hint)
            <div class="mt-2 text-xs text-slate-500">{{ $hint }}</div>
        @endif

        @if($href)
            <div class="mt-4 flex items-center justify-between">
                <span class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-1 text-xs font-medium text-white group-hover:bg-slate-800 transition">
                    Open
                </span>
            </div>
        @endif

@if($href)
    </a>
@else
    </div>
@endif
