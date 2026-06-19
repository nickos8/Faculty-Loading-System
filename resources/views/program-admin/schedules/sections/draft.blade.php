@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    {{-- PAGE HEADER --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="p-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                    Draft Schedule
                </h1>

                <p class="mt-1 text-sm text-slate-600">
                    {{ $section->name ?? ('Section #' . $section->id) }}
                </p>
            </div>

            <a href="{{ url()->previous() }}"
               class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-medium text-slate-700 hover:bg-slate-50">
                Back
            </a>
        </div>
    </div>

    {{-- SUBJECT SETUP --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Subject Setup</h2>
                <p class="text-xs text-slate-500">
                    Minutes per meeting are calculated automatically from units.
                </p>
            </div>

            <div class="text-xs text-slate-500">
                {{ $requiredSubjects->count() }} subject(s)
            </div>
        </div>

        @if($requiredSubjects->isEmpty())
            <div class="p-8 text-center text-sm text-slate-500">
                No subjects found for this section.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">
                            <th class="px-6 py-3 w-12"></th>
                            <th class="px-6 py-3">Subject</th>
                            <th class="px-6 py-3 whitespace-nowrap">Units</th>
                            <th class="px-6 py-3 whitespace-nowrap">Scheduled</th>
                            <th class="px-6 py-3 whitespace-nowrap">Meetings</th>
                            <th class="px-6 py-3 whitespace-nowrap">Minutes / Meeting</th>
                            <th class="px-6 py-3 whitespace-nowrap">Same Teacher</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @foreach($requiredSubjects as $index => $row)
                            @php
                                $isAlreadyScheduled = ($row['scheduled_minutes'] ?? 0) > 0;
                            @endphp

                            <tr class="hover:bg-slate-50/70"
                                data-row
                                data-index="{{ $index }}"
                                data-curriculum-term-subject-id="{{ $row['curriculum_term_subject_id'] }}"
                                data-subject-id="{{ $row['subject_id'] }}"
                                data-class-offering-id="{{ $row['class_offering_id'] }}"
                                data-subject-code="{{ $row['code'] }}"
                                data-subject-name="{{ $row['name'] }}"
                                data-units="{{ $row['units'] }}"
                                data-required-minutes="{{ $row['required_minutes'] }}"
                                data-scheduled-minutes="{{ $row['scheduled_minutes'] }}"
                                data-existing-meeting-count="{{ $row['existing_meeting_count'] }}">

                                <td class="px-6 py-4 align-top">
                                    <input type="checkbox"
                                           class="draft-enabled rounded border-slate-300 text-slate-900 focus:ring-slate-900"
                                           {{ $isAlreadyScheduled ? '' : 'checked' }}>
                                </td>

                                <td class="px-6 py-4 align-top">
                                    <div class="font-medium text-slate-900">
                                        {{ $row['code'] }}
                                    </div>

                                    <div class="text-xs text-slate-500">
                                        {{ $row['name'] }}
                                    </div>

                                    @if($isAlreadyScheduled)
                                        <div class="mt-1 text-xs text-slate-500">
                                            Already scheduled
                                        </div>
                                    @endif
                                </td>

                                <td class="px-6 py-4 align-top whitespace-nowrap text-slate-700">
                                    {{ number_format((float) $row['units'], 1) }}
                                </td>

                                <td class="px-6 py-4 align-top whitespace-nowrap">
                                    @if($isAlreadyScheduled)
                                        <span class="text-slate-600">
                                            {{ $row['scheduled_minutes'] }} mins
                                        </span>
                                    @else
                                        <span class="text-slate-400">
                                            0 mins
                                        </span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 align-top">
                                    <input type="number"
                                           min="1"
                                           max="7"
                                           value="{{ $isAlreadyScheduled && $row['existing_meeting_count'] > 0 ? $row['existing_meeting_count'] : 1 }}"
                                           class="meeting-count w-20 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/10 disabled:bg-slate-100 disabled:text-slate-400"
                                           {{ $isAlreadyScheduled ? 'disabled' : '' }}>
                                </td>

                                <td class="px-6 py-4 align-top">
                                    <div class="space-y-1">
                                        <input type="number"
                                               readonly
                                               value="{{ $row['required_minutes'] }}"
                                               class="duration-minutes w-24 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-900 disabled:bg-slate-100 disabled:text-slate-400"
                                               {{ $isAlreadyScheduled ? 'disabled' : '' }}>

                                        <div class="mini-summary text-[11px] text-slate-400"></div>
                                    </div>
                                </td>

                                <td class="px-6 py-4 align-top">
                                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                        <input type="checkbox"
                                               class="same-teacher rounded border-slate-300 text-slate-900 focus:ring-slate-900"
                                               checked
                                               {{ $isAlreadyScheduled ? 'disabled' : '' }}>
                                        <span>Yes</span>
                                    </label>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-slate-100 flex flex-wrap items-center justify-end gap-2">
                <button type="button"
                        id="generate-draft-btn"
                        class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-medium hover:bg-slate-800">
                    Generate Draft
                </button>
            </div>
        @endif
    </div>

    {{-- GENERATED RESULTS --}}
    <div id="draft-results-card"
         class="hidden rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">

        <div class="px-6 py-4 border-b border-slate-100 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Generated Draft</h2>
                <p class="text-xs text-slate-500">
                    Review the assigned teacher, schedule, and room before saving.
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">
                        <th class="px-6 py-3">Subject</th>
                        <th class="px-6 py-3">Teacher</th>
                        <th class="px-6 py-3">Schedule</th>
                        <th class="px-6 py-3">Room</th>
                        <th class="px-6 py-3">Mode</th>
                        <th class="px-6 py-3 text-right">Action</th>
                    </tr>
                </thead>

                <tbody id="draft-results-body" class="divide-y divide-slate-100">
                    {{-- JS inserts result rows here --}}
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-slate-100 flex flex-wrap items-center justify-end gap-2">
            <button type="button"
                    id="save-draft-btn"
                    disabled
                    class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 disabled:bg-slate-300 disabled:cursor-not-allowed">
                Save Draft Schedule
            </button>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const sectionId = @json($section->id);

    let lastGeneratedResults = [];

    function escapeHtml(value) {
        if (value === null || value === undefined) return '';

        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function toBackendTime(value) {
        if (!value) return null;

        const text = String(value);

        if (text.length === 5) {
            return `${text}:00`;
        }

        return text;
    }

    function labelTeacherStatus(status) {
        const labels = {
            preferred_match_with_availability: 'Preferred',
            same_program_fallback_with_availability: 'Same-program fallback',
            no_available_teacher: 'No teacher',
            no_available_preferred_teacher: 'No preferred teacher'
        };

        return labels[status] ?? status ?? '-';
    }

    function hasSaveableDraft(results) {
        return results && results.some((item) => item.status === 'room_assigned');
    }

    function removeDraftResult(indexToRemove) {
        lastGeneratedResults = lastGeneratedResults.filter((item, index) => {
            return index !== indexToRemove;
        });

        renderDraftResults(lastGeneratedResults);
    }

    function buildDraftReservations(excludeIndex) {
        const reservations = [];

        lastGeneratedResults.forEach((item, index) => {
            if (index === excludeIndex) {
                return;
            }

            if (!item.assigned_teacher || !item.meetings || !item.meetings.length) {
                return;
            }

            item.meetings.forEach((meeting) => {
                if (!meeting.day_of_week || !meeting.time_start || !meeting.time_end) {
                    return;
                }

                reservations.push({
                    teacher_id: parseInt(item.assigned_teacher.id, 10),
                    section_id: sectionId,
                    room_id: meeting.room_id ? parseInt(meeting.room_id, 10) : null,
                    day_of_week: parseInt(meeting.day_of_week, 10),
                    time_start: toBackendTime(meeting.time_start),
                    time_end: toBackendTime(meeting.time_end)
                });
            });
        });

        return reservations;
    }

    function buildAvoidSlotsFromCurrentResult(indexToRegenerate) {
        const item = lastGeneratedResults[indexToRegenerate];
        const avoidSlots = [];

        if (!item || !item.meetings || !item.meetings.length) {
            return avoidSlots;
        }

        item.meetings.forEach((meeting) => {
            if (!meeting.day_of_week || !meeting.time_start || !meeting.time_end) {
                return;
            }

            avoidSlots.push({
                teacher_id: item.assigned_teacher ? parseInt(item.assigned_teacher.id, 10) : null,
                section_id: sectionId,
                room_id: meeting.room_id ? parseInt(meeting.room_id, 10) : null,
                day_of_week: parseInt(meeting.day_of_week, 10),
                time_start: toBackendTime(meeting.time_start),
                time_end: toBackendTime(meeting.time_end)
            });
        });

        return avoidSlots;
    }

    function buildOfferingFromDraftResult(item) {
        return {
            curriculum_term_subject_id: parseInt(item.curriculum_term_subject_id, 10),
            subject_id: parseInt(item.subject_id, 10),
            class_offering_id: item.class_offering_id ? parseInt(item.class_offering_id, 10) : null,
            ignore_class_offering_id: item.ignore_class_offering_id ? parseInt(item.ignore_class_offering_id, 10) : null,
            is_reschedule: item.mode === 'reschedule',
            subject_code: item.subject_code,
            subject_name: item.subject_name,
            meeting_count: parseInt(item.meeting_count, 10),
            duration_minutes: parseInt(item.duration_minutes, 10),
            same_teacher_required: Boolean(item.same_teacher_required)
        };
    }

    async function regenerateDraftResult(indexToRegenerate) {
        const item = lastGeneratedResults[indexToRegenerate];

        if (!item) {
            alert('Draft row was not found.');
            return;
        }

        const confirmed = confirm('Regenerate this subject only? The system will try to avoid the current schedule first.');

        if (!confirmed) {
            return;
        }

        const payload = {
            section_id: sectionId,
            offerings: [
                buildOfferingFromDraftResult(item)
            ],
            draft_reservations: buildDraftReservations(indexToRegenerate),
            avoid_slots: buildAvoidSlotsFromCurrentResult(indexToRegenerate)
        };

        try {
            document.querySelectorAll('.regenerate-draft-row, .remove-draft-row').forEach((button) => {
                button.disabled = true;
            });

            const response = await fetch(
                `/program-admin/sections/${sectionId}/generate-draft`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                }
            );

            const result = await response.json();

            console.log('Regenerate Draft Response:', result);

            if (!response.ok) {
                alert(result.message ?? 'Regenerate failed.');
                return;
            }

            const regeneratedItem = result.results && result.results.length
                ? result.results[0]
                : null;

            if (!regeneratedItem) {
                alert('No regenerated result returned.');
                return;
            }

            lastGeneratedResults[indexToRegenerate] = regeneratedItem;

            renderDraftResults(lastGeneratedResults);

            alert('Subject regenerated successfully.');
        } catch (error) {
            console.error(error);
            alert('Error regenerating this subject.');
        } finally {
            document.querySelectorAll('.regenerate-draft-row, .remove-draft-row').forEach((button) => {
                button.disabled = false;
            });
        }
    }

    function isRowChecked(row) {
        return row.querySelector('.draft-enabled').checked;
    }

    function setRowInputsState(row) {
        const scheduledMinutes = parseInt(row.dataset.scheduledMinutes || 0, 10);
        const checked = isRowChecked(row);

        const meetingCountInput = row.querySelector('.meeting-count');
        const durationInput = row.querySelector('.duration-minutes');
        const sameTeacherInput = row.querySelector('.same-teacher');

        const shouldDisable = scheduledMinutes > 0 && !checked;

        meetingCountInput.disabled = shouldDisable;
        durationInput.disabled = shouldDisable;
        sameTeacherInput.disabled = shouldDisable;
    }

    function calculateRow(row) {
        setRowInputsState(row);

        const requiredMinutes = parseInt(row.dataset.requiredMinutes || 0, 10);
        const scheduledMinutes = parseInt(row.dataset.scheduledMinutes || 0, 10);

        const meetingCountInput = row.querySelector('.meeting-count');
        const durationInput = row.querySelector('.duration-minutes');
        const miniSummary = row.querySelector('.mini-summary');

        if (!isRowChecked(row)) {
            if (miniSummary) {
                miniSummary.textContent = scheduledMinutes > 0
                    ? 'Not included'
                    : 'Skipped';
            }

            return;
        }

        let meetingCount = parseInt(meetingCountInput.value || 1, 10);

        if (meetingCount < 1) meetingCount = 1;
        if (meetingCount > 7) meetingCount = 7;

        meetingCountInput.value = meetingCount;

        const durationPerMeeting = requiredMinutes / meetingCount;
        const roundedDuration = Math.round(durationPerMeeting);

        durationInput.value = roundedDuration;

        if (miniSummary) {
            if (scheduledMinutes > 0) {
                miniSummary.textContent = 'Will reschedule';
            } else {
                miniSummary.textContent = `${requiredMinutes} mins total`;
            }
        }
    }

    function buildDraftConfig() {
        const rows = document.querySelectorAll('[data-row]');
        const offerings = [];

        rows.forEach((row) => {
            calculateRow(row);

            if (!isRowChecked(row)) {
                return;
            }

            const scheduledMinutes = parseInt(row.dataset.scheduledMinutes || 0, 10);
            const classOfferingId = row.dataset.classOfferingId
                ? parseInt(row.dataset.classOfferingId, 10)
                : null;

            const isReschedule = scheduledMinutes > 0;

            offerings.push({
                curriculum_term_subject_id: parseInt(row.dataset.curriculumTermSubjectId, 10),
                subject_id: parseInt(row.dataset.subjectId, 10),
                class_offering_id: classOfferingId,
                ignore_class_offering_id: isReschedule ? classOfferingId : null,
                is_reschedule: isReschedule,
                subject_code: row.dataset.subjectCode,
                subject_name: row.dataset.subjectName,
                units: parseFloat(row.dataset.units || 0),
                required_minutes: parseInt(row.dataset.requiredMinutes || 0, 10),
                scheduled_minutes: scheduledMinutes,
                meeting_count: parseInt(row.querySelector('.meeting-count').value || 1, 10),
                duration_minutes: parseInt(row.querySelector('.duration-minutes').value || 0, 10),
                same_teacher_required: row.querySelector('.same-teacher').checked
            });
        });

        return {
            section_id: sectionId,
            offerings: offerings,
            draft_reservations: [],
            avoid_slots: []
        };
    }

    function renderDraftResults(results) {
        const card = document.getElementById('draft-results-card');
        const body = document.getElementById('draft-results-body');
        const saveBtn = document.getElementById('save-draft-btn');

        if (!card || !body) return;

        body.innerHTML = '';

        if (!results || !results.length) {
            body.innerHTML = `
                <tr>
                    <td colspan="6" class="px-6 py-6 text-center text-sm text-slate-500">
                        No draft results returned.
                    </td>
                </tr>
            `;

            card.classList.remove('hidden');

            if (saveBtn) {
                saveBtn.disabled = true;
            }

            return;
        }

        results.forEach((item, index) => {
            const subjectCode = escapeHtml(item.subject_code ?? '-');
            const subjectName = escapeHtml(item.subject_name ?? '-');

            const teacherName = item.assigned_teacher
                ? escapeHtml(item.assigned_teacher.name)
                : 'No teacher assigned';

            const teacherStatus = escapeHtml(labelTeacherStatus(item.teacher_status ?? '-'));

            const teacherExtra = item.assigned_teacher
                ? `
                    <div class="text-xs text-slate-500 mt-1">
                        ${teacherStatus}
                        • ${escapeHtml(item.assigned_teacher.active_units)} / ${escapeHtml(item.assigned_teacher.max_units)} units
                    </div>
                `
                : `
                    <div class="text-xs text-rose-600 mt-1">
                        ${teacherStatus}
                    </div>
                `;

            const schedules = item.meetings && item.meetings.length
                ? item.meetings.map((meeting) => {
                    const dayName = meeting.day_name
                        ? escapeHtml(meeting.day_name)
                        : 'No day';

                    const timeStart = meeting.time_start
                        ? escapeHtml(meeting.time_start.substring(0, 5))
                        : '--:--';

                    const timeEnd = meeting.time_end
                        ? escapeHtml(meeting.time_end.substring(0, 5))
                        : '--:--';

                    return `
                        <div class="leading-relaxed">
                            <span class="font-medium">${dayName}</span>
                            <span class="text-slate-500">${timeStart} - ${timeEnd}</span>
                            <div class="text-xs text-slate-400">
                                ${escapeHtml(meeting.duration_minutes)} mins
                            </div>
                        </div>
                    `;
                }).join('<div class="h-2"></div>')
                : '-';

            const rooms = item.meetings && item.meetings.length
                ? item.meetings.map((meeting) => {
                    const roomName = meeting.room_name
                        ? escapeHtml(meeting.room_name)
                        : 'No room';

                    return `
                        <div class="leading-relaxed ${meeting.room_name ? 'text-slate-700' : 'text-rose-600'}">
                            ${roomName}
                        </div>
                    `;
                }).join('<div class="h-6"></div>')
                : '-';

            const modeBadge = item.mode === 'reschedule'
                ? `<span class="text-xs font-medium text-slate-600">Reschedule</span>`
                : `<span class="text-xs font-medium text-slate-600">New</span>`;

            const warning = item.reason
                ? `
                    <div class="mt-2 text-xs text-amber-700 bg-amber-50 border border-amber-100 rounded-lg px-2 py-1">
                        ${escapeHtml(item.reason)}
                    </div>
                `
                : '';

            body.insertAdjacentHTML('beforeend', `
                <tr class="hover:bg-slate-50/70">
                    <td class="px-6 py-4 align-top">
                        <div class="font-medium text-slate-900">${subjectCode}</div>
                        <div class="text-xs text-slate-500">${subjectName}</div>
                        ${warning}
                    </td>

                    <td class="px-6 py-4 align-top">
                        <div class="${item.assigned_teacher ? 'text-slate-900' : 'text-rose-700'} font-medium">
                            ${teacherName}
                        </div>
                        ${teacherExtra}
                    </td>

                    <td class="px-6 py-4 align-top text-slate-700">
                        ${schedules}
                    </td>

                    <td class="px-6 py-4 align-top">
                        ${rooms}
                    </td>

                    <td class="px-6 py-4 align-top">
                        ${modeBadge}
                    </td>

                    <td class="px-6 py-4 align-top text-right">
                        <div class="flex items-center justify-end gap-3">
                            <button type="button"
                                    class="regenerate-draft-row text-xs font-medium text-slate-700 hover:text-slate-900 disabled:opacity-50"
                                    data-index="${index}">
                                Regenerate
                            </button>

                            <button type="button"
                                    class="remove-draft-row text-xs font-medium text-rose-600 hover:text-rose-700 disabled:opacity-50"
                                    data-index="${index}">
                                Remove
                            </button>
                        </div>
                    </td>
                </tr>
            `);
        });

        card.classList.remove('hidden');

        if (saveBtn) {
            saveBtn.disabled = !hasSaveableDraft(results);
        }

        document.querySelectorAll('.regenerate-draft-row').forEach((button) => {
            button.addEventListener('click', function () {
                const indexToRegenerate = parseInt(this.dataset.index, 10);

                if (Number.isNaN(indexToRegenerate)) {
                    return;
                }

                regenerateDraftResult(indexToRegenerate);
            });
        });

        document.querySelectorAll('.remove-draft-row').forEach((button) => {
            button.addEventListener('click', function () {
                const indexToRemove = parseInt(this.dataset.index, 10);

                if (Number.isNaN(indexToRemove)) {
                    return;
                }

                const confirmed = confirm('Remove this subject from the draft before saving?');

                if (!confirmed) {
                    return;
                }

                removeDraftResult(indexToRemove);
            });
        });

        card.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function refreshAllRows() {
        document.querySelectorAll('[data-row]').forEach((row) => {
            calculateRow(row);
        });
    }

    document.querySelectorAll('.meeting-count, .same-teacher, .draft-enabled').forEach((input) => {
        input.addEventListener('input', refreshAllRows);
        input.addEventListener('change', refreshAllRows);
    });

    const generateBtn = document.getElementById('generate-draft-btn');

    if (generateBtn) {
        generateBtn.addEventListener('click', async function () {
            const payload = buildDraftConfig();

            if (!payload.offerings.length) {
                alert('Please include at least one subject for draft generation.');
                return;
            }

            generateBtn.disabled = true;
            generateBtn.textContent = 'Generating...';

            try {
                const response = await fetch(
                    `/program-admin/sections/${payload.section_id}/generate-draft`,
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    }
                );

                const result = await response.json();

                console.log('Generate Draft Response:', result);

                if (!response.ok) {
                    alert(result.message ?? 'Generate draft failed.');
                    return;
                }

                lastGeneratedResults = result.results || [];

                renderDraftResults(lastGeneratedResults);

                alert(result.message ?? 'Draft generated successfully.');
            } catch (error) {
                console.error(error);
                alert('Error sending draft request.');
            } finally {
                generateBtn.disabled = false;
                generateBtn.textContent = 'Generate Draft';
            }
        });
    }

    const saveBtn = document.getElementById('save-draft-btn');

    if (saveBtn) {
        saveBtn.addEventListener('click', async function () {
            if (!hasSaveableDraft(lastGeneratedResults)) {
                alert('No complete draft rows to save. Only complete generated schedules can be saved.');
                return;
            }

            const confirmed = confirm(
                'Save this generated draft schedule? This will create or update class offerings and class meetings.'
            );

            if (!confirmed) {
                return;
            }

            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';

            try {
                const response = await fetch(
                    `/program-admin/sections/${sectionId}/save-draft`,
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            section_id: sectionId,
                            results: lastGeneratedResults
                        })
                    }
                );

                const result = await response.json();

                console.log('Save Draft Response:', result);

                if (!response.ok) {
                    alert(result.message ?? 'Save draft failed.');
                    return;
                }

                alert(result.message ?? 'Draft saved successfully.');

                window.location.reload();
            } catch (error) {
                console.error(error);
                alert('Error sending save draft request.');
            } finally {
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save Draft Schedule';
            }
        });
    }

    refreshAllRows();
});
</script>
@endsection
