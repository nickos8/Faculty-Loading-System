<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Schedule Report</title>

    <style>
        @page {
            margin: 32px 36px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10.5px;
            color: #111;
            line-height: 1.35;
        }

        .header {
            border-bottom: 2px solid #111;
            padding-bottom: 9px;
            margin-bottom: 12px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
        }

        .logo-cell {
            width: 86px;
        }

        .logo {
            width: 70px;
            height: 70px;
        }

        .school-name {
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 4px;
        }

        .school-meta {
            font-size: 10px;
            color: #333;
            margin-top: 2px;
        }

        .doc-meta {
            width: 220px;
            text-align: right;
            font-size: 9.5px;
            color: #333;
        }

        .doc-meta div {
            margin-bottom: 3px;
        }

        .doc-title {
            text-align: center;
            margin: 14px 0 14px;
        }

        .doc-title .name {
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .doc-title .sub {
            font-size: 10px;
            margin-top: 3px;
            color: #333;
        }

        .overview-box {
            border: 1px solid #111;
            padding: 9px;
            margin-bottom: 12px;
        }

        .overview-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 6px;
            border-bottom: 1px solid #999;
            padding-bottom: 4px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 3px 4px;
            vertical-align: top;
        }

        .label {
            font-weight: 700;
            width: 120px;
            color: #111;
        }

        .value {
            color: #222;
        }

        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0 14px;
        }

        .stats-table td {
            border: 1px solid #111;
            padding: 8px;
            text-align: center;
            width: 50%;
        }

        .stat-number {
            font-size: 16px;
            font-weight: 700;
            display: block;
            margin-bottom: 2px;
        }

        .stat-label {
            font-size: 9px;
            text-transform: uppercase;
            color: #333;
        }

        .section-title {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            margin: 16px 0 8px;
            padding-bottom: 4px;
            border-bottom: 1.5px solid #111;
        }

        .section-description {
            font-size: 9.5px;
            color: #333;
            margin-bottom: 9px;
        }

        .record-block {
            margin-bottom: 15px;
            page-break-inside: avoid;
        }

        .record-heading {
            border: 1px solid #111;
            border-bottom: none;
            background: #f2f2f2;
            padding: 7px;
        }

        .record-main {
            font-size: 11px;
            font-weight: 700;
        }

        .record-sub {
            font-size: 9px;
            color: #333;
            margin-top: 2px;
        }

        table.report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        table.report-table th,
        table.report-table td {
            border: 1px solid #111;
            padding: 5.5px;
            vertical-align: top;
            word-break: break-word;
        }

        table.report-table th {
            background: #f2f2f2;
            font-size: 9px;
            text-transform: uppercase;
            text-align: center;
            font-weight: 700;
        }

        table.report-table tr {
            page-break-inside: avoid;
        }

        thead {
            display: table-header-group;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .muted {
            font-size: 9px;
            color: #333;
        }

        .subject-code {
            font-weight: 700;
        }

        .empty-message {
            border: 1px solid #111;
            padding: 8px;
            text-align: center;
            color: #333;
            font-size: 10px;
        }

        .approval-section {
            margin-top: 28px;
            page-break-inside: avoid;
        }

        .approval-note {
            font-size: 10px;
            margin-bottom: 24px;
            text-align: justify;
        }

        .approval-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .approval-table td {
            width: 33.33%;
            text-align: center;
            padding: 38px 12px 0;
            font-size: 10px;
        }

        .signature-line {
            border-top: 1px solid #111;
            padding-top: 6px;
            font-weight: 700;
        }

        .signature-role {
            font-size: 9px;
            color: #333;
            margin-top: 2px;
        }

        .footer-note {
            margin-top: 20px;
            font-size: 9px;
            color: #444;
            text-align: center;
            border-top: 1px solid #999;
            padding-top: 6px;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>

<div class="header">
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                @if(file_exists(public_path('images/granbylogo.jpg')))
                    <img src="{{ public_path('images/granbylogo.jpg') }}" class="logo">
                @endif
            </td>
            <td>
                <div class="school-name">{{ $schoolName ?? 'YOUR SCHOOL' }}</div>
                <div class="school-meta">{{ $schoolAddress ?? '' }}</div>
                <div class="school-meta">{{ $schoolContact ?? '' }}</div>
            </td>
            <td class="doc-meta">
                <div><strong>Document No.:</strong> SR-{{ now()->format('Ymd') }}</div>
                <div><strong>Date Issued:</strong> {{ $dateIssued ?? now()->format('F d, Y') }}</div>
                <div><strong>Status:</strong> For Review and Approval</div>
            </td>
        </tr>
    </table>
</div>

<div class="doc-title">
    <div class="name">Schedule Report</div>
    <div class="sub">Faculty Loading and Section Schedule Report</div>
</div>

<div class="overview-box">
    <div class="overview-title">Report Overview</div>

    <table class="info-table">
        <tr>
            <td class="label">Document Type:</td>
            <td class="value">Official Schedule Report</td>
            <td class="label">Prepared By:</td>
            <td class="value">{{ $preparedBy ?: 'Scheduling Office' }}</td>
        </tr>
        <tr>
            <td class="label">Coverage:</td>
            <td class="value">Current active class offerings and class meetings</td>
            <td class="label">Generated:</td>
            <td class="value">{{ now()->format('M d, Y h:i A') }}</td>
        </tr>
    </table>

    <table class="stats-table">
        <tr>
            <td>
                <span class="stat-number">{{ $facultySchedules->count() }}</span>
                <span class="stat-label">Faculty with Assigned Schedules</span>
            </td>
            <td>
                <span class="stat-number">{{ $sectionSchedules->count() }}</span>
                <span class="stat-label">Active Sections</span>
            </td>
        </tr>
    </table>
</div>

<div class="section-title">Part I. Faculty Loads and Schedules</div>

<div class="section-description">
    This section presents the teaching load and assigned class meetings per faculty member.
</div>

@forelse ($facultySchedules as $faculty)
    <div class="record-block">
        <div class="record-heading">
            <div class="record-main">
                {{ $faculty->teacher_name }}
            </div>
            <div class="record-sub">
                @if($faculty->teacher_school_id)
                    Faculty ID: {{ $faculty->teacher_school_id }} |
                @endif
                Total Units: {{ $faculty->total_units }}
            </div>
        </div>

        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 10%;">Day</th>
                    <th style="width: 16%;">Time</th>
                    <th style="width: 23%;">Subject</th>
                    <th style="width: 17%;">Section</th>
                    <th style="width: 14%;">Program</th>
                    <th style="width: 8%;">Units</th>
                    <th style="width: 12%;">Room</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($faculty->rows as $row)
                    <tr>
                        <td>{{ $days[$row->day_of_week] ?? $row->day_of_week }}</td>
                        <td>
                            {{ $row->time_start ? \Carbon\Carbon::parse($row->time_start)->format('h:i A') : '—' }}
                            -
                            {{ $row->time_end ? \Carbon\Carbon::parse($row->time_end)->format('h:i A') : '—' }}
                        </td>
                        <td>
                            <span class="subject-code">{{ $row->subject_code }}</span><br>
                            <span class="muted">{{ $row->subject_name }}</span>
                        </td>
                        <td>
                            {{ $row->section_name }}<br>
                            <span class="muted">
                                Year {{ $row->year_level }} / Term {{ $row->term_no }}
                            </span>
                        </td>
                        <td>{{ $row->program_name }}</td>
                        <td class="text-center">{{ $row->units }}</td>
                        <td class="text-center">{{ $row->room_name }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@empty
    <div class="empty-message">No faculty schedules found.</div>
@endforelse

<div class="page-break"></div>

<div class="header">
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                @if(file_exists(public_path('images/granbylogo.jpg')))
                    <img src="{{ public_path('images/granbylogo.jpg') }}" class="logo">
                @endif
            </td>
            <td>
                <div class="school-name">{{ $schoolName ?? 'YOUR SCHOOL' }}</div>
                <div class="school-meta">{{ $schoolAddress ?? '' }}</div>
                <div class="school-meta">{{ $schoolContact ?? '' }}</div>
            </td>
            <td class="doc-meta">
                <div><strong>Document No.:</strong> SR-{{ now()->format('Ymd') }}</div>
                <div><strong>Date Issued:</strong> {{ $dateIssued ?? now()->format('F d, Y') }}</div>
                <div><strong>Status:</strong> For Review and Approval</div>
            </td>
        </tr>
    </table>
</div>

<div class="doc-title">
    <div class="name">Schedule Report</div>
    <div class="sub">Section Schedules</div>
</div>

<div class="section-title">Part II. Section Schedules</div>

<div class="section-description">
    This section presents the class schedule arrangement per active section.
</div>

@forelse ($sectionSchedules as $sectionSchedule)
    <div class="record-block">
        <div class="record-heading">
            <div class="record-main">
                {{ $sectionSchedule->section->section_name }}
            </div>
            <div class="record-sub">
                {{ $sectionSchedule->section->program_name }}
                | Year {{ $sectionSchedule->section->year_level }}
                | Term {{ $sectionSchedule->section->term_no }}
                @if($sectionSchedule->section->curriculum_code)
                    | Curriculum: {{ $sectionSchedule->section->curriculum_code }}
                @endif
            </div>
        </div>

        @if ($sectionSchedule->rows->count())
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 10%;">Day</th>
                        <th style="width: 16%;">Time</th>
                        <th style="width: 26%;">Subject</th>
                        <th style="width: 22%;">Faculty</th>
                        <th style="width: 8%;">Units</th>
                        <th style="width: 10%;">Room</th>
                        <th style="width: 8%;">Type</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sectionSchedule->rows as $row)
                        <tr>
                            <td>{{ $days[$row->day_of_week] ?? $row->day_of_week }}</td>
                            <td>
                                {{ $row->time_start ? \Carbon\Carbon::parse($row->time_start)->format('h:i A') : '—' }}
                                -
                                {{ $row->time_end ? \Carbon\Carbon::parse($row->time_end)->format('h:i A') : '—' }}
                            </td>
                            <td>
                                <span class="subject-code">{{ $row->subject_code }}</span><br>
                                <span class="muted">{{ $row->subject_name }}</span>
                            </td>
                            <td>{{ $row->teacher_first_name }} {{ $row->teacher_last_name }}</td>
                            <td class="text-center">{{ $row->units }}</td>
                            <td class="text-center">{{ $row->room_name }}</td>
                            <td class="text-center">{{ $row->subject_type ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty-message">No schedule assigned to this section.</div>
        @endif
    </div>
@empty
    <div class="empty-message">No active sections found.</div>
@endforelse

<div class="approval-section">
    <div class="section-title">Review and Approval</div>

    <p class="approval-note">
        This report is submitted for review and approval of the current faculty loading
        and section schedules. The schedules reflected in this document are based on the
        current active class offerings and class meetings encoded in the system.
    </p>

    <table class="approval-table">
        <tr>
            <td>
                <div class="signature-line">Prepared By</div>
                <div class="signature-role">Scheduling Office</div>
            </td>
            <td>
                <div class="signature-line">Reviewed By</div>
                <div class="signature-role">Academic Coordinator / Registrar</div>
            </td>
            <td>
                <div class="signature-line">Approved By</div>
                <div class="signature-role">President / Authorized Representative</div>
            </td>
        </tr>
    </table>
</div>

<div class="footer-note">
    This is a system-generated schedule report from the Faculty Loading and Schedule Management System.
</div>

</body>
</html>
