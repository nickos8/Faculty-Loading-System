@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-6 space-y-6">

    {{-- Glass Banner Header --}}
    <div class="rounded-3xl border border-white/40 bg-sky-50/60 backdrop-blur-xl shadow-sm">
        <div class="p-6 sm:p-8 flex items-start justify-between gap-4 flex-wrap">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">
                    Edit Section
                </h1>
                <p class="text-sm text-slate-600 mt-1">
                    Update details for <span class="font-semibold">{{ $section->name }}</span>
                </p>
            </div>

            <a href="{{ route('sections.index') }}"
               class="inline-flex items-center rounded-2xl bg-white px-4 py-2 text-sm text-slate-700 shadow-sm border border-slate-200 hover:bg-slate-50 transition">
                Back to Sections
            </a>
        </div>
    </div>

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-800 shadow-sm">
            <div class="font-semibold mb-1">Please fix the following:</div>
            <ul class="list-disc list-inside text-sm space-y-1">
                @foreach ($errors->all() as $msg)
                    <li>{{ $msg }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Success --}}
    @if (session('status'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 shadow-sm">
            {{ session('status') }}
        </div>
    @endif

    {{-- Form Card --}}
    <div class="rounded-2xl border border-slate-200 bg-white/70 backdrop-blur shadow-sm">
        <form method="POST"
              action="{{ route('sections.update', $section) }}"
              class="p-6 space-y-6">
            @csrf
            @method('PATCH')

            {{-- Name --}}
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">
                    Section Name
                </label>
                <input name="name"
                       value="{{ old('name', $section->name) }}"
                       class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-200">
                @error('name')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Capacity --}}
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">
                    Capacity
                </label>
                <input name="capacity"
                       type="number"
                       min="1"
                       value="{{ old('capacity', $section->capacity) }}"
                       class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-200">
                @error('capacity')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Year & Term --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">
                        Year Level
                    </label>
                    <input name="year_level"
                           type="number"
                           value="{{ old('year_level', $section->year_level) }}"
                           class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-200">
                    @error('year_level')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">
                        Term
                    </label>
                    <input name="term_no"
                           type="number"
                           value="{{ old('term_no', $section->term_no) }}"
                           class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-200">
                    @error('term_no')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Status --}}
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">
                    Status
                </label>
                <select name="status"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-200">
                    <option value="active" @selected(old('status', $section->status) === 'active')>
                        Active
                    </option>
                    <option value="archived" @selected(old('status', $section->status) === 'archived')>
                        Archived
                    </option>
                </select>
                @error('status')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Actions --}}
            <div class="pt-4 flex items-center justify-end gap-2">
                <a href="{{ route('sections.index') }}"
                   class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                    Cancel
                </a>

                <button type="submit"
                        class="rounded-xl bg-slate-900 px-5 py-2 text-sm font-medium text-white shadow-sm hover:bg-slate-800">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
