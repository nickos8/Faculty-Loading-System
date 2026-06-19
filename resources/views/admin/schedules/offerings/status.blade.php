@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6 space-y-6">

    {{-- FLASH / ERRORS (dashboard style) --}}
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 shadow-sm">
            <div class="font-semibold">Success</div>
            <div class="text-sm mt-1">{{ session('success') }}</div>
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-800 shadow-sm">
            <div class="font-semibold mb-1">Please fix the following:</div>
            <ul class="list-disc list-inside text-sm space-y-1">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- HEADER (dashboard style) --}}
    <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
        <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">
                    Offering Finalization Status
                </h1>
                <p class="text-sm text-slate-600">
                    Review whether this offering is finalized, locked, or unlocked — and manage unlocking if needed.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.schedules.offerings.index') }}"
                   class="px-3 py-2 text-xs font-medium rounded-xl bg-white border border-slate-200 text-slate-900 hover:bg-slate-50">
                    ← Back
                </a>
            </div>
        </div>
    </div>

    {{-- OFFERING SUMMARY --}}
    <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
        <div class="px-6 py-4 flex items-center justify-between">
            <div>
                <div class="text-sm font-semibold text-slate-900">Offering details</div>
                <div class="text-xs text-slate-500">Quick reference for the selected offering.</div>
            </div>

            <div class="text-xs text-slate-500">
                Offering <span class="font-medium text-slate-700">#{{ $offering->id }}</span>
            </div>
        </div>

        <div class="px-6 pb-6">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <dt class="text-xs font-medium text-slate-500">Section</dt>
                    <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $offering->section->name ?? 'N/A' }}</dd>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <dt class="text-xs font-medium text-slate-500">Program</dt>
                    <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $offering->section->program->program_name ?? 'N/A' }}</dd>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4 sm:col-span-2">
                    <dt class="text-xs font-medium text-slate-500">Subject</dt>
                    <dd class="mt-1 text-sm text-slate-900">
                        <span class="font-semibold">
                            {{ $offering->curriculumTermSubject->subject->code ?? '' }}
                        </span>
                        <span class="text-slate-400">—</span>
                        <span class="font-medium">
                            {{ $offering->curriculumTermSubject->subject->name ?? 'N/A' }}
                        </span>
                    </dd>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4 sm:col-span-2">
                    <dt class="text-xs font-medium text-slate-500">Dates</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900">{{ $offering->start_date }} → {{ $offering->end_date }}</dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- STATUS + ACTIONS --}}
    <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
        <div class="px-6 py-4 flex items-center justify-between">
            <div>
                <div class="text-sm font-semibold text-slate-900">Finalization status</div>
                <div class="text-xs text-slate-500">This determines whether editing is locked for teachers.</div>
            </div>
        </div>

        <div class="px-6 pb-6 space-y-4">

            @if(!$finalization || !$finalization->finalized_at)
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex items-center justify-between gap-3 flex-wrap">
                        <div class="text-sm font-semibold text-slate-900">Status</div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full border border-slate-200 bg-white text-xs font-semibold text-slate-700">
                            Draft (Not Finalized)
                        </span>
                    </div>
                    <div class="mt-2 text-xs text-slate-500">Nothing to unlock.</div>
                </div>

            @elseif($isFinalized)
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-900">
                    <div class="flex items-center justify-between gap-3 flex-wrap">
                        <div class="text-sm font-semibold">Status</div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full border border-amber-200 bg-white text-xs font-semibold text-amber-800">
                            Finalized (Locked)
                        </span>
                    </div>

                    <div class="mt-3 text-sm text-amber-900/90">
                        <div>Finalized at: <span class="font-medium">{{ $finalization->finalized_at }}</span></div>
                        <div>
                            Finalized by:
                            <span class="font-medium">
                                {{ optional($finalization->finalizedBy)->first_name }} {{ optional($finalization->finalizedBy)->last_name }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-100">
                    <form method="POST" action="{{ route('admin.schedules.offerings.unlock', $offering->id) }}" class="space-y-4">
                        @csrf

                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">
                                Unlock reason <span class="text-rose-600">*</span>
                            </label>
                            <textarea name="unlock_reason"
                                      rows="3"
                                      required
                                      class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-900 shadow-sm
                                             focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                                      placeholder="Explain why this offering needs to be unlocked…">{{ old('unlock_reason') }}</textarea>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div class="text-xs text-slate-500">
                                Unlocking allows teachers to edit evaluations again.
                            </div>

                            <button type="submit"
                                    onclick="return confirm('Unlock this offering? Teachers can edit evaluations again.');"
                                    class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-rose-200 bg-rose-50 text-rose-700 text-sm font-medium hover:bg-rose-100">
                                Unlock / Unfinalize
                            </button>
                        </div>
                    </form>
                </div>

            @else
                <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sky-900">
                    <div class="flex items-center justify-between gap-3 flex-wrap">
                        <div class="text-sm font-semibold">Status</div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full border border-sky-200 bg-white text-xs font-semibold text-sky-800">
                            Unlocked (After Finalize)
                        </span>
                    </div>

                    <div class="mt-3 text-sm text-sky-900/90 space-y-2">
                        <div>
                            <div>Finalized at: <span class="font-medium">{{ $finalization->finalized_at }}</span></div>
                            <div>
                                Finalized by:
                                <span class="font-medium">
                                    {{ optional($finalization->finalizedBy)->first_name }} {{ optional($finalization->finalizedBy)->last_name }}
                                </span>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-sky-200/60">
                            <div>Unlocked at: <span class="font-medium">{{ $finalization->unlocked_at }}</span></div>
                            <div>
                                Unlocked by:
                                <span class="font-medium">
                                    {{ optional($finalization->unlockedBy)->first_name }} {{ optional($finalization->unlockedBy)->last_name }}
                                </span>
                            </div>
                            <div class="mt-2">
                                <div class="text-xs font-medium text-sky-800">Reason</div>
                                <div class="text-sm text-sky-900">{{ $finalization->unlock_reason }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

</div>
@endsection
