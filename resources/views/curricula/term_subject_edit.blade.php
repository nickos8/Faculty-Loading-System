@extends('layouts.app')

@section('content')
@php
    $termLabel = "Year {$term->year_level}, Term {$term->term_no}";
@endphp

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-semibold text-slate-900">Edit Curriculum Subject</h1>
                <p class="text-sm text-slate-600 mt-1">
                    {{ $termLabel }} • Curriculum ID: {{ $term->curriculum_id }}
                </p>
            </div>

            <a href="{{ route('curricula.show', $term->curriculum_id) }}"
               class="px-3 py-2 text-xs font-semibold rounded-xl border border-slate-200 bg-white hover:bg-slate-50">
                Back
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-800">
            <div class="font-semibold">Please fix the errors below.</div>
            <ul class="list-disc ml-5 text-sm mt-2 space-y-1">
                @foreach($errors->all() as $e)
                    <li class="whitespace-pre-line">{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('terms.subjects.update', [$term->id, $cts->id]) }}" method="POST"
          class="rounded-2xl border border-slate-200 bg-white shadow-sm p-6 space-y-5">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-xs font-medium text-slate-600 mb-2">Subject</label>
            <select name="subject_id"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                @foreach($choices as $opt)
                    <option value="{{ $opt->id }}"
                        @selected(old('subject_id', $cts->subject_id) == $opt->id)>
                        {{ $opt->code }} — {{ $opt->name }}
                    </option>
                @endforeach
            </select>
            <p class="text-xs text-slate-500 mt-2">
                Note: If this subject already has class offerings, changing the subject is blocked for safety.
            </p>
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-600 mb-2">Units</label>
            <input type="number" step="0.5" min="0.5" max="10"
                   name="unit"
                   value="{{ old('unit', $cts->unit) }}"
                   class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-600 mb-2">Subject Type</label>
            <select name="type"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                @foreach(['major','minor','elective','general','thesis','internship'] as $t)
                    <option value="{{ $t }}" @selected(old('type', $cts->type) === $t)>
                        {{ strtoupper($t) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex flex-wrap gap-2 justify-end">
            <a href="{{ route('curricula.show', $term->curriculum_id) }}"
               class="px-4 py-2 text-xs font-semibold rounded-xl border border-slate-200 bg-white hover:bg-slate-50">
                Cancel
            </a>
            <button type="submit"
                    class="px-4 py-2 text-xs font-semibold rounded-xl bg-slate-900 text-white hover:bg-slate-800">
                Save Changes
            </button>
        </div>
    </form>
</div>
@endsection
