<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Section Schedule</title>
    <style>
        /* ===== PAGE + TYPE (PORTRAIT FORMAL) ===== */
        @page { margin: 22px 35px 18px 35px; } /* top right bottom left */
        /*@page { margin: TOP RIGHT BOTTOM LEFT; }
 */
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111;
            line-height: 1.25;
        }

        /* ===== HEADER (UNCHANGED STRUCTURE, SLIGHTLY MORE FORMAL) ===== */
        .header { margin-bottom: 8px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; }
        .school-name { font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; }
        .school-meta { font-size: 10px; margin-top: 2px; color:#333; }
        .muted { font-size: 10px; color:#333; }

        .line { border-top: 2px solid #111; margin: 10px 0 12px; }

        .title { text-align:center; margin: 0; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: .35px; }
        .subtitle { text-align:center; margin-top: 3px; font-size: 10.5px; color:#333; }

        .info { width: 100%; border-collapse: collapse; margin: 10px 0 10px; }
        .info td { padding: 3px 0; }
        .label { width: 120px; font-weight: 700; }

        tr { page-break-inside: avoid; }

        .section-label {
            margin: 10px 0 6px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .25px;
        }

        /* ===== GRID (PORTRAIT FIT) ===== */
        table.grid { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 6px; }
        table.grid th, table.grid td { border: 1px solid #2b2b2b; }

        table.grid th {
            background: #efefef;
            text-transform: uppercase;
            font-size: 9px;
            padding: 5px 2px;
            text-align: center;
            letter-spacing: .2px;
        }

        /* Portrait: narrower time columns */
        .timeCell {
            width: 44px;
            font-size: 8.6px;
            text-align: center;
            padding: 4px 2px;
            white-space: nowrap;
            background: #fafafa;
        }

        /* Portrait: slightly smaller row height & text */
        .slotCell {
            height: 16px;
            padding: 2px 2px;
            vertical-align: top;
            text-align: center;
            font-size: 8.5px;
        }

        .block { line-height: 1.12; }
        .block .subj { font-weight: 700; font-size: 8.6px; margin-bottom: 1px; }
        .block .room, .block .teacher { margin-top: 1px; font-size: 8.2px; color:#222; }

        /* ===== SUMMARY ===== */
        .summary-label {
            margin: 12px 0 6px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .25px;
        }

        table.summary { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.summary th, table.summary td { border: 1px solid #2b2b2b; }

        table.summary th {
            background: #efefef;
            text-transform: uppercase;
            font-size: 9px;
            padding: 5px 3px;
            text-align: center;
            letter-spacing: .2px;
        }
        table.summary td { font-size: 9px; padding: 5px 4px; vertical-align: top; }

        /* ===== FOOTER ===== */
        .notes { margin-top: 16px; font-size: 10px; color:#222; }
        .notes strong { text-transform: uppercase; letter-spacing: .2px; }

        .sig { width: 100%; margin-top: 34px; border-collapse: collapse; }
        .sig td { width: 50%; vertical-align: top; }
        .sigline { border-top: 1px solid #111; width: 80%; margin-top: 16px; }

        .pagenum:before { content: counter(page); }
    </style>
</head>
<body>

@php
    use Carbon\Carbon;

    // Base possible days (Mon..Sat) - filter unused days automatically
    $baseDayKeys = [1,2,3,4,5,6];

    $scheduledDays = collect($rows ?? [])
        ->pluck('day')
        ->map(fn($d) => (int)$d)
        ->filter(fn($d) => in_array($d, $baseDayKeys, true))
        ->unique()
        ->sort()
        ->values()
        ->all();

    $dayKeys = count($scheduledDays) ? $scheduledDays : $baseDayKeys;

    // Portrait tip: if too many day columns (like 5-6 days), grid gets tight.
    // This template still works, but text becomes smaller. (We already reduced sizes.)

    // 30-minute slots
    $startTime = '07:00';
    $endTime   = '17:00';

    $slots = [];
    $cursor = Carbon::createFromFormat('H:i', $startTime);
    $end    = Carbon::createFromFormat('H:i', $endTime);

    while ($cursor < $end) {
        $next = $cursor->copy()->addMinutes(30);
        $slots[] = [$cursor->format('H:i'), $next->format('H:i')];
        $cursor = $next;
    }

    $slotIndexByTime = [];
    foreach ($slots as $i => $s) {
        $slotIndexByTime[$s[0]] = $i;
    }

    $toGridTime = function($t){
        try { return Carbon::createFromFormat('H:i', $t)->format('g:i'); }
        catch (\Throwable $e) { return $t; }
    };

    $grid = [];
    $skip = [];
    foreach ($dayKeys as $d) { $grid[$d] = []; $skip[$d] = []; }

    foreach (collect($rows ?? []) as $r) {
        $d = (int)($r['day'] ?? 0);
        if (!in_array($d, $dayKeys, true)) continue;

        $st = substr((string)($r['start'] ?? ''), 0, 5);
        $en = substr((string)($r['end'] ?? ''), 0, 5);

        if (!isset($slotIndexByTime[$st])) continue;

        $startIndex = $slotIndexByTime[$st];

        try {
            $m1 = Carbon::createFromFormat('H:i', $st);
            $m2 = Carbon::createFromFormat('H:i', $en);
            $minutes = $m1->diffInMinutes($m2, false);
        } catch (\Throwable $e) {
            $minutes = 30;
        }

        $span = max(1, (int) round($minutes / 30));

        $subject = e($r['subject'] ?? '—');
        $room    = e($r['room'] ?? '—');
        $teacher = e($r['teacher'] ?? '—');

        $content =
            '<div class="block">' .
                '<div class="subj">'.$subject.'</div>' .
                '<div class="room">'.$room.'</div>' .
                '<div class="teacher">'.$teacher.'</div>' .
            '</div>';

        if (isset($grid[$d][$startIndex])) {
            $grid[$d][$startIndex]['content'] .=
                '<div style="border-top:1px solid #2b2b2b;margin:2px 0;"></div>' . $content;

            $grid[$d][$startIndex]['rowspan'] = max($grid[$d][$startIndex]['rowspan'], $span);
            $span = $grid[$d][$startIndex]['rowspan'];
        } else {
            $grid[$d][$startIndex] = ['rowspan' => $span, 'content' => $content];
        }

        for ($k = 1; $k < $span; $k++) {
            $skip[$d][$startIndex + $k] = true;
        }
    }
@endphp

<!-- ===== HEADER (UNCHANGED CONTENT) ===== -->
<div class="header">
    <table class="header-table">
        <tr>
            <td style="width: 90px;">
                <img src="{{ public_path('images/granbylogo.jpg') }}" style="width:72px; height:72px;">
            </td>
            <td>
                <div class="school-name">{{ $schoolName }}</div>
                <div class="school-meta">{{ $schoolAddress }}</div>
                <div class="school-meta">{{ $schoolContact }}</div>
            </td>
            <td style="width: 200px; text-align:right;">
                <div class="muted"><strong>Document No.:</strong> SEC-{{ $section->id }}-{{ now()->format('Ymd') }}</div>
                <div class="muted"><strong>Date Issued:</strong> {{ now()->format('F d, Y') }}</div>
            </td>
        </tr>
    </table>
</div>

<div class="line"></div>

<div class="title">Section {{ $section->name ?? '—' }} Schedule</div>
<div class="subtitle">{{ $termLabel }}</div>

<table class="info">
    <tr>
        <td class="label">Program:</td>
        <td>{{ $section->program_name ?? '—' }}</td>
        <td class="label">Curriculum:</td>
        <td>{{ $section->curriculum_code ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">Year Level:</td>
        <td>{{ $section->year_level ?? '—' }}</td>
        <td class="label" style="padding-left:20px;">Term No.:</td>
        <td>{{ $section->term_no ?? '—' }}</td>
    </tr>
</table>

<div class="section-label">Weekly Schedule Grid</div>

<!-- ===== GRID (PORTRAIT) ===== -->
<table class="grid">
    <thead>
        <tr>
            <th colspan="2">Time</th>
            @foreach($dayKeys as $d)
                <th>{{ $days[$d] ?? $d }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($slots as $idx => $slot)
            <tr>
                <td class="timeCell">{{ $toGridTime($slot[0]) }}</td>
                <td class="timeCell">{{ $toGridTime($slot[1]) }}</td>

                @foreach($dayKeys as $d)
                    @if(!empty($skip[$d][$idx]))
                        {{-- covered by rowspan above --}}
                    @elseif(isset($grid[$d][$idx]))
                        <td class="slotCell" rowspan="{{ $grid[$d][$idx]['rowspan'] }}">
                            {!! $grid[$d][$idx]['content'] !!}
                        </td>
                    @else
                        <td class="slotCell"></td>
                    @endif
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>

<div class="summary-label">Subject Offerings</div>

<table class="summary">
    <thead>
        <tr>
            <th style="width: 16%;">Subj Code</th>
            <th>Subject Title</th>
            <th style="width: 10%;">Units</th>
            <th style="width: 30%;">Instructor</th>
        </tr>
    </thead>
    <tbody>
        @forelse(($subjectSummary ?? []) as $s)
            <tr>
                <td style="text-align:center;">{{ $s['code'] ?? '—' }}</td>
                <td>{{ $s['title'] ?? '—' }}</td>
                <td style="text-align:center;">{{ $s['units'] ?? '—' }}</td>
                <td>{{ $s['instructors'] ?? '—' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" style="text-align:center;">No subjects found.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<!-- ===== FOOTER (UNCHANGED CONTENT) ===== -->
<div class="notes">
    <strong>Notes:</strong>
    <ol style="margin: 6px 0 0 16px;">
        <li>This document is system-generated and valid for official use.</li>
        <li>Any corrections must be requested through the Scheduling Office.</li>
    </ol>
</div>

<table class="sig">
    <tr>
        <td>
            <div class="sigline"></div>
            <div><strong>PROGRAM HEAD</strong></div>
        </td>
        <td>
            <div class="sigline"></div>
            <div><strong>PRESIDENT</strong></div>
        </td>
    </tr>
</table>

<div class="muted" style="margin-top: 12px;">Page <span class="pagenum"></span></div>

</body>
</html>
