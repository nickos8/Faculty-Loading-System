@props([
    'href',
    'label',
])

<a href="{{ $href }}"
   class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
    {{ $label }}
</a>
