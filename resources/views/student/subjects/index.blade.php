@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8 space-y-6">

    {{-- HEADER (dashboard style) --}}
    <div class="rounded-2xl border border-white/40 bg-white/70 backdrop-blur shadow-sm">
        <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">My Subjects</h1>
                <p class="text-sm text-slate-500">Your currently enrolled subjects for this term.</p>
            </div>


            <div class="flex items-center gap-2">
                 <a href="{{ route('student.schedule.show') }}"
                   class="px-3 py-2 text-xs font-medium rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50">
                     Back to Schedule
                </a>
            </div>
        </div>
    </div>

  

    @if($enrolledSubjects->isEmpty())
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-800 shadow-sm">
            <div class="font-semibold">No enrolled subjects</div>
            <div class="text-sm mt-1">You have no active subjects for this term yet.</div>
        </div>
    @else

        {{-- SECTION + SUMMARY CARDS --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

            {{-- Section / Program --}}
            <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-xs">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Section</p>
                <p class="mt-1 text-base font-semibold text-slate-900">{{ $section->name }}</p>
                <p class="text-xs text-slate-500 mt-0.5">{{ $section->program_name }}</p>
            </div>

            {{-- Total Subjects --}}
            <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-xs">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total Subjects</p>
                <p class="mt-1 text-3xl font-bold text-slate-900">{{ $enrolledSubjects->count() }}</p>
                <p class="text-xs text-slate-500 mt-0.5">this term</p>
            </div>

            {{-- Total Units --}}
            <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-xs">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total Units</p>
                <p class="mt-1 text-3xl font-bold text-slate-900">{{ number_format($totalUnits, 1) }}</p>
                <p class="text-xs text-slate-500 mt-0.5">credit units enrolled</p>
            </div>

        </div>



        {{-- SUBJECTS TABLE --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-900">Enrolled Subjects</h2>
                <span class="text-xs text-slate-500">
                    {{ $enrolledSubjects->count() }} subject{{ $enrolledSubjects->count() === 1 ? '' : 's' }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-xs sm:text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold text-slate-600 uppercase tracking-wide">
                            <th class="px-6 py-3">#</th>
                            <th class="px-6 py-3">Code</th>
                            <th class="px-6 py-3">Subject Name</th>
                            <th class="px-6 py-3">Units</th>
                            <th class="px-6 py-3">Type</th>

                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($enrolledSubjects as $i => $cts)
                            @php $subj = $cts->subject; @endphp
                            <tr class="hover:bg-slate-50/60 transition-colors">

                                {{-- Row number --}}
                                <td class="px-6 py-4 text-slate-400 font-medium">
                                    {{ $i + 1 }}
                                </td>

                                {{-- Code --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-mono font-semibold text-slate-900 bg-slate-100 px-2 py-0.5 rounded-md text-xs">
                                        {{ $subj?->code ?? '—' }}
                                    </span>
                                </td>

                                {{-- Name --}}
                                <td class="px-6 py-4 text-slate-800 font-medium">
                                    {{ $subj?->name ?? '—' }}
                                </td>

                                {{-- Units --}}
                                <td class="px-6 py-4 whitespace-nowrap text-slate-700">
                                    @if($cts->unit)
                                        <span class="font-semibold text-slate-900">{{ number_format((float)$cts->unit, 1) }}</span>

                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>

                                {{-- Type badge --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($cts->type)
                                        <span class="inline-flex items-center px-2.5 py-0.5  text-xs font-semibold
                                            {{ match($cts->type) {
                                                'major'      => ' text-blue-700',
                                                'minor'      => ' text-purple-700',
                                                'elective'   => ' text-amber-700',
                                                'general'    => ' text-green-700',
                                                'thesis'     => ' text-rose-700',
                                                'internship' => ' text-teal-700',
                                                default      => ' text-slate-600',
                                            } }}">
                                            {{ ucfirst($cts->type) }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>



                            </tr>
                        @endforeach
                    </tbody>

                    {{-- Footer: totals row --}}
                    <tfoot class="bg-slate-50 border-t border-slate-200">
                        <tr>
                            <td colspan="3" class="px-6 py-3 text-xs font-semibold text-slate-600 text-right">
                                Total
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <span class="font-bold text-slate-900">{{ number_format($totalUnits, 1) }}</span>
                                <span class="text-slate-400 text-xs ml-0.5">units</span>
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    @endif

</div>
@endsection
