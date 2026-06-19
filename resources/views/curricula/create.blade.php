@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6 space-y-6">

    {{-- HEADER (dashboard style) --}}
    <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
        <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">Create Curriculum</h1>
                <p class="text-sm text-slate-600">
                    Program:
                    <span class="font-medium text-slate-900">{{ $program->program_name ?? '—' }}</span>
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('curricula.index') }}"
                   class="px-3 py-2 text-xs font-medium rounded-xl bg-white border border-slate-200 text-slate-900 hover:bg-slate-50">
                    Back to Curricula
                </a>
            </div>
        </div>
    </div>

    {{-- ERRORS (dashboard style) --}}
    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-800 shadow-sm">
            <div class="font-semibold mb-1">Please fix the following:</div>
            <ul class="list-disc list-inside text-sm space-y-1">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- FORM CARD --}}
    <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
        <form action="{{ route('curricula.store') }}" method="POST" class="p-6 space-y-6">
            @csrf

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                <span class="font-semibold text-slate-900">Tip:</span>
                Use a versioned code so it’s easy to track updates (e.g., <span class="font-medium">BSIT-2025</span>).
            </div>

            <div>
                <label for="code" class="block text-xs font-medium text-slate-500 mb-1">Curriculum Code</label>
                <input
                    id="code"
                    type="text"
                    name="code"
                    value="{{ old('code') }}"
                    placeholder="e.g., BSIT-2025"
                    required
                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                           focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                >
                @error('code')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- ACTIONS (dashboard style) --}}
            <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <a href="{{ route('curricula.index') }}"
                   class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-medium text-slate-900 hover:bg-slate-50">
                    Cancel
                </a>

                <button type="submit"
                        class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800">
                    Create Curriculum
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
