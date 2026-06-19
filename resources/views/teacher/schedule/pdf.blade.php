@php
    use Carbon\Carbon;

    // Optional: customize these (or pass from controller)
    $schoolName    = $schoolName ?? 'Granby Colleges Of Science and Technology';
    $schoolAddress = $schoolAddress ?? 'Ibayo silangan, Naic, Cavite, Philippines';
    $schoolContact = $schoolContact ?? 'Tel: (63) 111-2222 • Email: Granby@gmail.com';

    // Optional: school year/term (if you have it)
    $termLabel = $termLabel ?? 'School Year: ____________';
@endphp

<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Teacher Schedule</title>
    <style>
        /* DOMPDF-friendly CSS */
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        .page { padding: 18px 22px 18px 22px; }

        .header { border-bottom: 2px solid #111; padding-bottom: 8px; margin-bottom: 10px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; }

        .logo-box {
            width: 72px; height: 72px;
            border: 1px solid #111;
            text-align: center;
            line-height: 72px;
            font-size: 10px;
        }

        .school-name { font-size: 10px; font-weight: 700; letter-spacing: .3px; text-transform: uppercase; }
        .school-meta { font-size: 10px; margin-top: 2px; }

        .doc-title { text-align: center; margin: 12px 0 10px; }
        .doc-title .name { font-size: 13px; font-weight: 700; text-transform: uppercase; }
        .doc-title .sub  { font-size: 10px; margin-top: 2px; }

        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .info-table td { padding: 2px 0; }

        .label { font-weight: 700; width: 140px; }


        .schedule-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .schedule-table th, .schedule-table td { border: 1px solid #111; padding: 6px; }
        .schedule-table th {
            font-weight: 700;
            text-transform: uppercase;
            font-size: 10px;
            background: #f2f2f2;
        }

        .footer { margin-top: 16px; }
        .signatures { width: 100%; border-collapse: collapse; margin-top: 18px; }
        .signatures td { width: 33.33%; vertical-align: top; padding-top: 22px; }
        .sig-line { border-top: 1px solid #111; margin-top: 28px; width: 90%; }
        .sig-label { font-size: 10px; margin-top: 4px; }

        .notes { font-size: 10px; margin-top: 12px; }
        .muted { font-size: 10px; color: #333; }

        /* Page number (DOMPDF supports this) */
        @page { margin: 18px 22px; }
        .pagenum:before { content: counter(page); }
    </style>
</head>

<body>
<div class="page">

    <!-- HEADER / LETTERHEAD -->
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width: 90px;">
                    {{-- Replace box with actual logo if you have one in /public --}}
                    <img src="{{ public_path('images/granbylogo.jpg') }}" style="width:72px; height:72px;">

                </td>
                <td>
                    <div class="school-name">{{ $schoolName }}</div>
                    <div class="school-meta">{{ $schoolAddress }}</div>
                    <div class="school-meta">{{ $schoolContact }}</div>
                </td>
                <td style="width: 180px; text-align:right;">
                    <div class="muted"><strong>Document No.:</strong> TS-{{ is_object($teacher) ? $teacher->id : $teacher }}-{{ now()->format('Ymd') }}</div>
                    <div class="muted"><strong>Date Issued:</strong> {{ now()->format('F d, Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- DOCUMENT TITLE -->
    <div class="doc-title">
        <div class="name">Teacher Schedule</div>
        <div class="sub">{{ $termLabel }}</div>
    </div>

    <!-- TEACHER INFO -->
    <table class="info-table">
        <tr>
            <td class="label">Teacher Name </td>
            <td class="value"> : {{  is_object($teacher) ? $teacher->first_name . ' ' . $teacher->last_name : $teacher }}  </td>
            <td style="width: 24px;"></td>
            <td class="label">Schedule Range</td>
            <td class="value"> : {{ ucfirst($range) }}</td>
        </tr>
        <tr>
            <td class="label">Weekly Load</td>
            <td class="value"> : {{ $weeklyHours }}h {{ $weeklyMinutesRemainder }}m</td>
            <td></td>
            <td class="label">Monthly Load</td>
            <td class="value"> : {{ $monthlyHours }}h {{ $monthlyMinutesRemainder }}m</td>
        </tr>
    </table>

    <!-- SCHEDULE TABLE -->
    <table class="schedule-table">
        <thead>
            <tr>
                <th style="width: 10%;">Day</th>
                <th style="width: 18%;">Time</th>
                <th>Subject</th>
                <th style="width: 15%;">Section</th>
                <th style="width: 15%;">Room</th>
            </tr>
        </thead>
        <tbody>
        @forelse($meetings as $m)
            <tr>
                <td>{{ $days[$m->day_of_week] ?? $m->day_of_week }}</td>
                <td>
                    {{ Carbon::parse($m->time_start)->format('h:i A') }}
                    -
                    {{ Carbon::parse($m->time_end)->format('h:i A') }}
                </td>
                <td>{{ $m->offering?->curriculumTermSubject?->subject?->name ?? '—' }}</td>
                <td>{{ $m->offering?->section?->name ?? '—' }}</td>
                <td>{{ $m->room?->name ?? '—' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" style="text-align:center;">No schedule found.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <!-- FOOTER NOTES + SIGNATURES -->
    <div class="footer">
        <div class="notes">
            <strong>Notes:</strong>
            <ol style="margin: 6px 0 0 16px; padding: 0;">
                <li>This document is system-generated and valid for official use.</li>
                <li>Any corrections must be requested through the Scheduling Office.</li>
                <li>Please present this document when required for administrative verification.</li>
            </ol>
        </div>

        <table class="signatures">
            <tr>
                <td>
                    <div class="sig-line"></div>
                    <div class="sig-label"><strong>PRESIDENT</strong><br></div>
                </td>
                <td>
                    <div class="sig-line"></div>
                    <div class="sig-label"><strong>PROGRAM HEAD</strong><br></div>
                </td>

            </tr>
        </table>

        <div class="muted" style="margin-top: 10px;">
            Page <span class="pagenum"></span>
        </div>
    </div>

</div>
</body>
</html>
