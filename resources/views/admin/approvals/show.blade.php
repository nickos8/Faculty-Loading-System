@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    {{-- FLASH / ERRORS (dashboard style) --}}
    @if (session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 shadow-sm">
            <div class="font-semibold">Success</div>
            <div class="text-sm mt-1">{{ session('success') }}</div>
        </div>
    @endif

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

    {{-- HEADER (dashboard style) --}}
    <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
        <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900">
                    Review Applicant
                </h1>
                <p class="text-sm text-slate-600">
                    Verify details and documents before approving or declining the account.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.approvals.index') }}"
                   class="px-3 py-2 text-xs font-medium rounded-xl bg-white border border-slate-200 text-slate-900 hover:bg-slate-50">
                     Back to Approvals
                </a>
            </div>
        </div>
    </div>


    {{-- APPLICANT SUMMARY (card) --}}
<div class="rounded-2xl border border-slate-200/70 bg-gray-100 shadow-sm">
    <div class="p-6">
        <div class="grid gap-6 lg:grid-cols-12">

         {{-- LEFT: APPLICANT INFO --}}
<div class="lg:col-span-5">
    <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">

        {{-- ✅ this creates the space inside the border --}}
        <div class="p-6">

            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="text-xs font-medium text-slate-500">Applicant</div>
                    <div class="mt-1 text-lg font-semibold text-slate-900 truncate">
                        {{ trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: ($user->name ?? 'Applicant') }}
                    </div>
                </div>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                {{-- Email --}}
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-[11px] font-medium text-slate-500">Email</div>
                    <div class="mt-1 text-sm font-semibold text-slate-900 break-words">
                        {{ $user->email ?? '—' }}
                    </div>
                </div>

                {{-- Phone --}}
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-[11px] font-medium text-slate-500">Phone Number</div>
                    <div class="mt-1 text-sm font-semibold text-slate-900">
                        {{ $user->phone_number ?? '—' }}
                    </div>
                </div>

                {{-- School ID --}}
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-[11px] font-medium text-slate-500">School ID</div>
                    <div class="mt-1 text-sm font-semibold text-slate-900">
                        {{ $user->school_id ?? '—' }}
                    </div>
                </div>

                {{-- Gender --}}
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-[11px] font-medium text-slate-500">Gender</div>
                    <div class="mt-1 text-sm font-semibold text-slate-900">
                        {{ $user->gender ?? '—' }}
                    </div>
                </div>
            </div>

            {{-- Program --}}
            <div class="mt-3 rounded-xl border border-slate-200 bg-white px-4 py-3">
                <div class="text-[11px] font-medium text-slate-500">Program</div>
                <div class="mt-1 text-sm font-semibold text-slate-900 break-words">
                    {{ $program->program_name ?? '—' }}
                </div>
            </div>

            @if($user->hasRole('teacher'))
                <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="text-[11px] font-medium text-slate-500">Employment Type</div>
                    <div class="mt-1 text-sm font-semibold text-slate-900">
                        @php $type = $user->teacherLoadSetting->employment_type ?? null; @endphp

                        @if($type === 'regular')
                            Regular
                        @elseif($type === 'part_time')
                            Part-time
                        @else
                            —
                        @endif
                    </div>

                    @if($user->teacherLoadSetting)
                        <div class="mt-1 text-xs text-slate-500">
                            Max Units: {{ rtrim(rtrim(number_format($user->teacherLoadSetting->max_units, 2), '0'), '.') }}
                        </div>
                    @endif
                </div>
            @endif

            {{-- Address --}}
            <div class="mt-3 rounded-xl border border-slate-200 bg-white px-4 py-3">
                <div class="text-[11px] font-medium text-slate-500">Address</div>
                <div class="mt-1 text-sm font-semibold text-slate-900 break-words">
                    {{ $user->address ?? '—' }}
                </div>
            </div>

        </div>
    </div>
</div>



            {{-- MIDDLE: DOCUMENTS --}}
            <div class="lg:col-span-4">
                <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
                    <div class="px-6 py-4 flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-slate-900">Documents</div>
                            <div class="text-xs text-slate-500">Preview in panel or open in new tab.</div>
                        </div>
                        <div class="text-xs text-slate-500 shrink-0">
                            Total: <span class="font-semibold text-slate-700">{{ $user->documents->count() }}</span>
                        </div>
                    </div>

                    @if($user->documents->isNotEmpty())
                        <div class="px-6 pb-6 space-y-3">
                            @foreach($user->documents as $doc)
                                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="text-sm font-semibold text-slate-900 truncate">
                                                {{ $doc->original_name }}
                                            </div>
                                            <div class="text-xs text-slate-500 mt-1">
                                                {{ number_format($doc->size / 1024, 1) }} KB
                                            </div>

                                            <div class="mt-2">
                                                <a href="{{ route('admin.approvals.show', ['user' => $user->id]) }}?doc={{ $doc->id }}"
                                                class="text-xs font-medium text-slate-600 hover:text-slate-900">
                                                    Preview in panel
                                                </a>
                                            </div>
                                        </div>


                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="px-6 py-10 text-center">
                            <div class="text-sm font-semibold text-slate-900">No documents uploaded</div>
                            <div class="mt-1 text-xs text-slate-500">Ask the applicant to upload requirements.</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- RIGHT: DECISION --}}
            <div class="lg:col-span-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="text-sm font-semibold text-slate-900">Decision</div>
                            <div class="mt-1 text-xs text-slate-500">Approve or decline this applicant.</div>
                        </div>
                    </div>

                    <div class="mt-4 space-y-3">
                        {{-- APPROVE --}}
                        <form action="{{ route('admin.approvals.approve', $user) }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="w-full inline-flex items-center justify-center px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-semibold shadow-sm hover:bg-emerald-700">
                                Approve
                            </button>
                        </form>

                        <div class="h-px bg-slate-100"></div>

                        {{-- DECLINE --}}
                        <form method="POST"
                              action="{{ route('admin.approvals.decline', $user->id) }}"
                              onsubmit="return confirm('Decline this applicant?');">
                            @csrf

                            <label class="block text-xs font-medium text-slate-600 mb-1">
                                Decline reason
                            </label>

                            <textarea
                                name="note"
                                rows="4"
                                required
                                placeholder="Write a short reason..."
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm
                                       focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-300"
                            >{{ old('note') }}</textarea>

                            <button type="submit"
                                    class="mt-3 w-full inline-flex items-center justify-center px-4 py-2 rounded-xl bg-rose-600 text-white text-sm font-semibold shadow-sm hover:bg-rose-700">
                                Decline
                            </button>
                        </form>

                        <div class="pt-2 text-[11px] text-slate-500">
                            Tip: Open PDF in a new tab for full-screen viewing.
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>


    {{-- MAIN GRID: Documents + Preview --}}
    <div class="grid gap-6 lg:grid-cols-12">

        {{-- RIGHT: PDF PREVIEW (FULL WIDTH FIX) --}}
        <div class="lg:col-span-12">
            <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
                <div class="px-6 py-4 flex items-center justify-between gap-4">
                    <div>
                        <div class="text-sm font-semibold text-slate-900">Preview</div>
                        <div class="text-xs text-slate-500">Embedded PDF viewer (if available).</div>
                    </div>

                    @if($firstDoc)
                        <div class="text-xs text-slate-500 max-w-[60%] truncate text-right">
                            {{ $firstDoc->original_name }}
                        </div>
                    @endif
                </div>

                @if($firstDoc)
                    <div class="border-t border-slate-100">
                        <embed
                            src="{{ route('admin.approvals.document.show', ['user' => $user->id, 'doc' => $firstDoc->id]) }}"
                            type="application/pdf"
                            class="w-full"
                            style="height:72vh;"
                        />
                    </div>
                @else
                    <div class="px-6 py-12 text-center text-slate-500">
                        No document to preview.
                    </div>
                @endif
            </div>
        </div>

    </div>

</div>
@endsection
