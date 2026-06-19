<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Student Schedule</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color:#111; }
        .header { border-bottom: 2px solid #111; padding-bottom: 8px; margin-bottom: 10px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; }
        .school-name { font-size: 14px; font-weight: 700; text-transform: uppercase; }
        .school-meta { font-size: 10px; margin-top: 2px; }
        .muted { font-size: 10px; color:#333; }

        .doc-title { text-align: center; margin: 12px 0 10px; }
        .doc-title .name { font-size: 13px; font-weight: 700; text-transform: uppercase; }
        .doc-title .sub  { font-size: 10px; margin-top: 2px; }

        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .info-table td { padding: 2px 0; }
        .label { font-weight: 700; width: 140px; }

        table.schedule { width: 100%; border-collapse: collapse; }
        table.schedule th, table.schedule td { border: 1px solid #111; padding: 6px; }
        table.schedule th { background: #f2f2f2; font-size: 10px; text-transform: uppercase; }
    </style>
</head>
<body>

<div class="header">
    <table class="header-table">
        <tr>
            <td style="width: 90px;">
                {{-- Put your logo in public/images/granbylogo.jpg --}}
                <img src="{{ public_path('images/granbylogo.jpg') }}" style="width:72px; height:72px;">
            </td>
            <td>
                <div class="school-name">{{ $schoolName ?? 'YOUR SCHOOL' }}</div>
                <div class="school-meta">{{ $schoolAddress ?? '' }}</div>
                <div class="school-meta">{{ $schoolContact ?? '' }}</div>
            </td>
            <td style="width: 200px; text-align:right;">
                <div class="muted"><strong>Document No.:</strong> SS-{{ $student->id }}-{{ now()->format('Ymd') }}</div>
                <div class="muted"><strong>Date Issued:</strong> {{ now()->format('F d, Y') }}</div>
            </td>
        </tr>
    </table>
</div>

<div class="doc-title">
    <div class="name">Student Schedule</div>
    <div class="sub">{{ $termLabel ?? '' }}</div>
</div>

<table class="info-table">
    <tr>
        <td class="label">Student Name</td>
        <td>{{ $student->first_name }} {{ $student->last_name }}</td>
        <td style="width:24px;"></td>
        <td class="label">Program</td>
        <td>{{ $section->program_name ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">Section</td>
        <td>{{ $section->name ?? '—' }}</td>
        <td></td>
        <td class="label">Generated</td>
        <td>{{ now()->format('M d, Y h:i A') }}</td>
    </tr>
</table>

<table class="schedule">
    <thead>
        <tr>
            <th style="width: 10%;">Day</th>
            <th style="width: 18%;">Time</th>
            <th>Subject</th>
            <th style="width: 20%;">Teacher</th>
            <th style="width: 14%;">Room</th>
        </tr>
    </thead>
    <tbody>
        @forelse($meetings as $m)
            <tr>
                <td>{{ $days[$m['day']] ?? $m['day'] }}</td>
                <td>{{ $m['start'] }} - {{ $m['end'] }}</td>
                <td>{{ $m['subject'] }}</td>
                <td>{{ $m['teacher'] }}</td>
                <td>{{ $m['room'] }}</td>
            </tr>
        @empty
            <tr><td colspan="5" style="text-align:center;">No schedule found.</td></tr>
        @endforelse
    </tbody>
</table>

</body>
</html>
