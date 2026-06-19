<?php

namespace App\Http\Controllers\ProgramAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class SectionDraftScheduleController extends Controller
{
    public function show($sectionId)
    {
        $user = Auth::user();

        if (!$user) {
            abort(403);
        }

        if (method_exists($user, 'hasRole') && !$user->hasRole('program_admin')) {
            abort(403, 'Only Program Admin can access this page.');
        }

        $today = now()->toDateString();

        $section = DB::table('sections as sec')
            ->join('programs as p', 'p.id', '=', 'sec.program_id')
            ->join('curricula as c', 'c.id', '=', 'sec.curriculum_id')
            ->where('sec.id', $sectionId)
            ->select([
                'sec.*',
                'p.program_name',
                'c.code as curriculum_code',
                'c.title as curriculum_title',
            ])
            ->first();

        abort_if(!$section, 404);

        if ($user->program_id && (int) $user->program_id !== (int) $section->program_id) {
            abort(403, 'You are not allowed to access this section.');
        }

        $term = DB::table('curriculum_terms as ct')
            ->where('ct.curriculum_id', $section->curriculum_id)
            ->where('ct.year_level', $section->year_level)
            ->where('ct.term_no', $section->term_no)
            ->where('ct.term_type', 'regular')
            ->orderBy('ct.sequence')
            ->first();

        $requiredSubjects = collect();

        if ($term) {
            $requiredSubjects = DB::table('curriculum_term_subjects as cts')
                ->join('subjects as subj', 'subj.id', '=', 'cts.subject_id')
                ->leftJoin('class_offerings as co', function ($join) use ($section, $today) {
                    $join->on('co.curriculum_term_subject_id', '=', 'cts.id')
                        ->where('co.section_id', '=', $section->id)
                        ->where('co.status', '=', 'active')
                        ->whereDate('co.end_date', '>', $today);
                })
                ->leftJoin('class_meetings as cm', function ($join) {
                    $join->on('cm.class_offering_id', '=', 'co.id')
                        ->whereNull('cm.deleted_at');
                })
                ->where('cts.curriculum_term_id', $term->id)
                ->where('cts.is_required', 1)
                ->where('subj.status', 'active')
                ->groupBy(
                    'cts.id',
                    'cts.subject_id',
                    'subj.code',
                    'subj.name',
                    'cts.unit',
                    'cts.type',
                    'co.id'
                )
                ->select([
                    'cts.id as curriculum_term_subject_id',
                    'cts.subject_id',
                    'subj.code',
                    'subj.name',
                    'co.id as class_offering_id',
                    DB::raw('cts.unit as units'),
                    DB::raw('cts.type as type'),
                    DB::raw('(cts.unit * 60) as required_minutes'),
                    DB::raw('COALESCE(SUM(TIMESTAMPDIFF(MINUTE, cm.time_start, cm.time_end)), 0) as scheduled_minutes'),
                    DB::raw('COUNT(cm.id) as existing_meeting_count'),
                ])
                ->orderBy('subj.code')
                ->get()
                ->map(function ($row) {
                    $requiredMinutes = (int) round($row->required_minutes);
                    $scheduledMinutes = (int) $row->scheduled_minutes;

                    return [
                        'curriculum_term_subject_id' => (int) $row->curriculum_term_subject_id,
                        'subject_id' => (int) $row->subject_id,
                        'class_offering_id' => $row->class_offering_id ? (int) $row->class_offering_id : null,
                        'code' => $row->code,
                        'name' => $row->name,
                        'units' => (float) $row->units,
                        'type' => $row->type,
                        'required_minutes' => $requiredMinutes,
                        'scheduled_minutes' => $scheduledMinutes,
                        'existing_meeting_count' => (int) $row->existing_meeting_count,
                        'is_already_scheduled' => $scheduledMinutes > 0,
                    ];
                })
                ->values();
        }

        return view('program-admin.schedules.sections.draft', [
            'section' => $section,
            'term' => $term,
            'requiredSubjects' => $requiredSubjects,
        ]);
    }

    public function generateDraft(Request $request, $sectionId)
    {
        $user = Auth::user();

        if (!$user) {
            abort(403);
        }

        if (method_exists($user, 'hasRole') && !$user->hasRole('program_admin')) {
            abort(403, 'Only Program Admin can generate draft schedules.');
        }

        $section = DB::table('sections')
            ->where('id', $sectionId)
            ->first();

        abort_if(!$section, 404);

        if ($user->program_id && (int) $user->program_id !== (int) $section->program_id) {
            abort(403, 'You are not allowed to generate a draft for this section.');
        }

        $data = $request->validate([
            'section_id' => ['required', 'integer'],
            'offerings' => ['required', 'array', 'min:1'],

            'offerings.*.curriculum_term_subject_id' => ['required', 'integer'],
            'offerings.*.subject_id' => ['required', 'integer'],
            'offerings.*.subject_code' => ['required', 'string'],
            'offerings.*.subject_name' => ['required', 'string'],

            'offerings.*.meeting_count' => ['required', 'integer', 'min:1', 'max:7'],
            'offerings.*.duration_minutes' => ['required', 'integer', 'min:1'],
            'offerings.*.same_teacher_required' => ['required', 'boolean'],

            'offerings.*.is_reschedule' => ['required', 'boolean'],
            'offerings.*.class_offering_id' => ['nullable', 'integer'],
            'offerings.*.ignore_class_offering_id' => ['nullable', 'integer'],

            // Phase 10B: other generated rows currently shown on the screen.
            'draft_reservations' => ['nullable', 'array'],
            'draft_reservations.*.teacher_id' => ['required_with:draft_reservations', 'integer'],
            'draft_reservations.*.section_id' => ['required_with:draft_reservations', 'integer'],
            'draft_reservations.*.room_id' => ['nullable', 'integer'],
            'draft_reservations.*.day_of_week' => ['required_with:draft_reservations', 'integer', 'min:1', 'max:7'],
            'draft_reservations.*.time_start' => ['required_with:draft_reservations', 'date_format:H:i:s'],
            'draft_reservations.*.time_end' => ['required_with:draft_reservations', 'date_format:H:i:s'],

            // Phase 10B improvement: current row slots to avoid first.
            'avoid_slots' => ['nullable', 'array'],
            'avoid_slots.*.teacher_id' => ['nullable', 'integer'],
            'avoid_slots.*.section_id' => ['nullable', 'integer'],
            'avoid_slots.*.room_id' => ['nullable', 'integer'],
            'avoid_slots.*.day_of_week' => ['required_with:avoid_slots', 'integer', 'min:1', 'max:7'],
            'avoid_slots.*.time_start' => ['required_with:avoid_slots', 'date_format:H:i:s'],
            'avoid_slots.*.time_end' => ['required_with:avoid_slots', 'date_format:H:i:s'],
        ]);

        if ((int) $data['section_id'] !== (int) $sectionId) {
            return response()->json([
                'message' => 'Section mismatch.',
            ], 422);
        }

        $today = now()->toDateString();
        $results = [];

        /*
        |--------------------------------------------------------------------------
        | Draft reservations
        |--------------------------------------------------------------------------
        | Normal Generate Draft starts empty.
        | Regenerate one row starts with the other generated rows so the new row
        | will avoid conflicts with drafts already shown on screen.
        */
        $draftReservations = collect($data['draft_reservations'] ?? [])
            ->map(function ($reservation) {
                return [
                    'teacher_id' => (int) $reservation['teacher_id'],
                    'section_id' => (int) $reservation['section_id'],
                    'room_id' => !empty($reservation['room_id']) ? (int) $reservation['room_id'] : null,
                    'day_of_week' => (int) $reservation['day_of_week'],
                    'time_start' => $reservation['time_start'],
                    'time_end' => $reservation['time_end'],
                ];
            })
            ->values()
            ->all();

        /*
        |--------------------------------------------------------------------------
        | Avoid slots
        |--------------------------------------------------------------------------
        | These are the current slots of the row being regenerated.
        | The scheduler will try to avoid these first, so Regenerate can return
        | a different schedule when another valid option exists.
        */
        $avoidSlots = collect($data['avoid_slots'] ?? [])
            ->map(function ($slot) use ($sectionId) {
                return [
                    'teacher_id' => !empty($slot['teacher_id']) ? (int) $slot['teacher_id'] : null,
                    'section_id' => !empty($slot['section_id']) ? (int) $slot['section_id'] : (int) $sectionId,
                    'room_id' => !empty($slot['room_id']) ? (int) $slot['room_id'] : null,
                    'day_of_week' => (int) $slot['day_of_week'],
                    'time_start' => $slot['time_start'],
                    'time_end' => $slot['time_end'],
                ];
            })
            ->values()
            ->all();

        foreach ($data['offerings'] as $offering) {
            $subjectId = (int) $offering['subject_id'];
            $meetingCount = (int) $offering['meeting_count'];
            $durationMinutes = (int) $offering['duration_minutes'];

            $isReschedule = (bool) $offering['is_reschedule'];
            $classOfferingId = $offering['class_offering_id'] ?? null;
            $ignoreClassOfferingId = $offering['ignore_class_offering_id'] ?? null;

            /*
            |--------------------------------------------------------------------------
            | Reschedule safety check
            |--------------------------------------------------------------------------
            | If an offering is expired, treat it as a new draft.
            */
            if ($isReschedule && $classOfferingId) {
                $existingOffering = DB::table('class_offerings')
                    ->where('id', $classOfferingId)
                    ->where('section_id', $sectionId)
                    ->first();

                if (!$existingOffering) {
                    $results[] = $this->manualReviewResult(
                        $offering,
                        'Existing offering was not found.'
                    );

                    continue;
                }

                if ($existingOffering->end_date <= $today) {
                    $isReschedule = false;
                    $classOfferingId = null;
                    $ignoreClassOfferingId = null;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Subject program
            |--------------------------------------------------------------------------
            | Used for same-program fallback teacher assignment.
            */
            $subjectInfo = DB::table('subjects')
                ->where('id', $subjectId)
                ->select(['id', 'program_id'])
                ->first();

            $subjectProgramId = $subjectInfo && $subjectInfo->program_id
                ? (int) $subjectInfo->program_id
                : (int) $section->program_id;

            /*
            |--------------------------------------------------------------------------
            | Subject units
            |--------------------------------------------------------------------------
            */
            $subjectUnits = (float) DB::table('curriculum_term_subjects')
                ->where('id', (int) $offering['curriculum_term_subject_id'])
                ->value('unit');

            /*
            |--------------------------------------------------------------------------
            | Teacher load subquery
            |--------------------------------------------------------------------------
            | Teacher load is based on class_meetings.teacher_id.
            | One subject can have many meetings, but units count only once.
            */
            $offeringUnitsSub = DB::table('class_meetings as cm')
                ->join('class_offerings as co', 'co.id', '=', 'cm.class_offering_id')
                ->join('curriculum_term_subjects as cts', 'cts.id', '=', 'co.curriculum_term_subject_id')
                ->whereNull('cm.deleted_at')
                ->where('co.status', 'active')
                ->whereDate('co.end_date', '>', $today)
                ->when($ignoreClassOfferingId, function ($query) use ($ignoreClassOfferingId) {
                    $query->where('co.id', '!=', $ignoreClassOfferingId);
                })
                ->selectRaw('cm.teacher_id, co.id as class_offering_id, cts.unit')
                ->distinct();

            $loadSub = DB::query()
                ->fromSub($offeringUnitsSub, 'active_teacher_offerings')
                ->groupBy('teacher_id')
                ->selectRaw('teacher_id, COALESCE(SUM(unit), 0) as active_units');

            /*
            |--------------------------------------------------------------------------
            | Availability subquery
            |--------------------------------------------------------------------------
            | A teacher must have enough available days where the time window
            | can fit the meeting duration.
            */
            $availabilitySub = DB::table('teacher_availabilities as ta')
                ->whereRaw(
                    'TIMESTAMPDIFF(MINUTE, ta.start_time, ta.end_time) >= ?',
                    [$durationMinutes]
                )
                ->groupBy('ta.user_id')
                ->selectRaw('ta.user_id, COUNT(DISTINCT ta.day) as available_day_count');

            /*
            |--------------------------------------------------------------------------
            | Teacher selection with same-program fallback
            |--------------------------------------------------------------------------
            | Preferred teachers are still priority.
            */
            $teacherCandidates = DB::table('users as u')
                ->join('user_roles as ur', 'ur.user_id', '=', 'u.id')
                ->join('roles as r', 'r.id', '=', 'ur.role_id')
                ->leftJoin('teacher_preferred_subjects as tps', function ($join) use ($subjectId) {
                    $join->on('tps.teacher_id', '=', 'u.id')
                        ->where('tps.subject_id', '=', $subjectId);
                })
                ->leftJoinSub($loadSub, 'tl', function ($join) {
                    $join->on('tl.teacher_id', '=', 'u.id');
                })
                ->leftJoinSub($availabilitySub, 'av', function ($join) {
                    $join->on('av.user_id', '=', 'u.id');
                })
                ->leftJoin('teacher_load_settings as tls', 'tls.user_id', '=', 'u.id')
                ->where('r.name', 'teacher')
                ->where('u.status', 'active')
                ->where(function ($query) use ($subjectProgramId) {
                    $query->whereNotNull('tps.id')
                        ->orWhere('u.program_id', $subjectProgramId);
                })
                ->whereRaw('COALESCE(tls.max_units, 0) > 0')
                ->whereRaw(
                    '(COALESCE(tl.active_units, 0) + ?) <= COALESCE(tls.max_units, 0)',
                    [$subjectUnits]
                )
                ->whereRaw('COALESCE(av.available_day_count, 0) >= ?', [$meetingCount])
                ->select([
                    'u.id',
                    'u.first_name',
                    'u.last_name',
                    'u.program_id',
                    DB::raw('CASE WHEN tps.id IS NOT NULL THEN 1 ELSE 0 END as is_preferred_match'),
                    DB::raw('CASE WHEN tps.id IS NOT NULL THEN "preferred" ELSE "same_program_fallback" END as match_type'),
                    DB::raw('COALESCE(tps.preference_level, 0) as preference_level'),
                    DB::raw('COALESCE(tl.active_units, 0) as active_units'),
                    DB::raw('COALESCE(tls.max_units, 0) as max_units'),
                    DB::raw('COALESCE(av.available_day_count, 0) as available_day_count'),
                ])
                ->orderByRaw('CASE WHEN tps.id IS NOT NULL THEN 1 ELSE 0 END DESC')
                ->orderByDesc('tps.preference_level')
                ->orderBy('active_units')
                ->orderByDesc('available_day_count')
                ->orderBy('u.last_name')
                ->orderBy('u.first_name')
                ->get();

            $assignedTeacher = $teacherCandidates->first();

            /*
            |--------------------------------------------------------------------------
            | Time slot + conflict check + room assignment
            |--------------------------------------------------------------------------
            */
            $possibleSlots = [];
            $selectedSlots = [];

            if ($assignedTeacher) {
                $possibleSlots = $this->generateTeacherTimeSlots(
                    (int) $assignedTeacher->id,
                    $durationMinutes
                );

                $selectedSlots = $this->pickNonConflictingSlotsWithRoomsForMeetings(
                    $possibleSlots,
                    $meetingCount,
                    (int) $assignedTeacher->id,
                    (int) $sectionId,
                    $ignoreClassOfferingId ? (int) $ignoreClassOfferingId : null,
                    $draftReservations,
                    $avoidSlots
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Build draft meetings
            |--------------------------------------------------------------------------
            */
            $meetings = [];

            for ($i = 0; $i < $meetingCount; $i++) {
                $slot = $selectedSlots[$i] ?? null;

                $meetings[] = [
                    'meeting_number' => $i + 1,
                    'duration_minutes' => $durationMinutes,
                    'day_name' => $slot['day_name'] ?? null,
                    'day_of_week' => $slot['day_of_week'] ?? null,
                    'time_start' => $slot['time_start'] ?? null,
                    'time_end' => $slot['time_end'] ?? null,
                    'room_id' => $slot['room_id'] ?? null,
                    'room_name' => $slot['room_name'] ?? null,
                    'room_capacity' => $slot['room_capacity'] ?? null,
                    'status' => $slot ? 'room_assigned' : 'no_conflict_free_slot_with_room',
                ];
            }

            $hasEnoughRooms = $assignedTeacher && count($selectedSlots) >= $meetingCount;

            $teacherStatus = 'no_available_teacher';

            if ($assignedTeacher) {
                $teacherStatus = $assignedTeacher->match_type === 'preferred'
                    ? 'preferred_match_with_availability'
                    : 'same_program_fallback_with_availability';
            }

            $results[] = [
                'curriculum_term_subject_id' => (int) $offering['curriculum_term_subject_id'],
                'subject_id' => $subjectId,
                'subject_code' => $offering['subject_code'],
                'subject_name' => $offering['subject_name'],

                'mode' => $isReschedule ? 'reschedule' : 'new',
                'class_offering_id' => $classOfferingId,
                'ignore_class_offering_id' => $ignoreClassOfferingId,

                'meeting_count' => $meetingCount,
                'duration_minutes' => $durationMinutes,
                'same_teacher_required' => (bool) $offering['same_teacher_required'],

                'subject_program_id' => $subjectProgramId,

                'teacher_status' => $teacherStatus,

                'assigned_teacher' => $assignedTeacher ? [
                    'id' => $assignedTeacher->id,
                    'name' => trim($assignedTeacher->first_name . ' ' . $assignedTeacher->last_name),
                    'program_id' => $assignedTeacher->program_id ? (int) $assignedTeacher->program_id : null,
                    'match_type' => $assignedTeacher->match_type,
                    'is_preferred_match' => (bool) $assignedTeacher->is_preferred_match,
                    'preference_level' => (int) $assignedTeacher->preference_level,
                    'active_units' => (float) $assignedTeacher->active_units,
                    'max_units' => (float) $assignedTeacher->max_units,
                    'after_assignment_units' => (float) $assignedTeacher->active_units + $subjectUnits,
                    'available_day_count' => (int) $assignedTeacher->available_day_count,
                ] : null,

                'meetings' => $meetings,
                'possible_slots' => $possibleSlots,

                'status' => $hasEnoughRooms ? 'room_assigned' : 'manual_review',

                'reason' => $hasEnoughRooms
                    ? null
                    : (
                        $assignedTeacher
                            ? 'Teacher was selected, but not enough conflict-free slots with available rooms were found.'
                            : 'No preferred teacher or same-program fallback teacher found with enough load and availability.'
                    ),
            ];
        }

        return response()->json([
            'message' => 'Draft processed with preferred teacher priority, same-program fallback, availability filtering, conflict checking, and room assignment, and avoid-current-row regeneration.',
            'results' => $results,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Phase 9: Save Draft Schedule
    |--------------------------------------------------------------------------
    | Important change:
    | This version fully deletes old class_meetings for the offering before
    | inserting the new saved schedule.
    */
    public function saveDraft(Request $request, $sectionId)
    {
        $user = Auth::user();

        if (!$user) {
            abort(403);
        }

        if (method_exists($user, 'hasRole') && !$user->hasRole('program_admin')) {
            abort(403, 'Only Program Admin can save draft schedules.');
        }

        $data = $request->validate([
            'section_id' => ['required', 'integer'],
            'results' => ['required', 'array', 'min:1'],
        ]);

        if ((int) $data['section_id'] !== (int) $sectionId) {
            return response()->json([
                'message' => 'Section mismatch.',
            ], 422);
        }

        $section = DB::table('sections')
            ->where('id', $sectionId)
            ->first();

        if (!$section) {
            return response()->json([
                'message' => 'Section was not found.',
            ], 404);
        }

        if ($user->program_id && (int) $user->program_id !== (int) $section->program_id) {
            abort(403, 'You are not allowed to save a draft for this section.');
        }

        /*
        |--------------------------------------------------------------------------
        | Curriculum term dates are the source of truth.
        |--------------------------------------------------------------------------
        */
        $term = DB::table('curriculum_terms')
            ->where('curriculum_id', $section->curriculum_id)
            ->where('year_level', $section->year_level)
            ->where('term_no', $section->term_no)
            ->where('term_type', 'regular')
            ->orderBy('sequence')
            ->first();

        if (!$term) {
            return response()->json([
                'message' => 'Curriculum term was not found for this section.',
            ], 422);
        }

        if (!$term->start_date || !$term->end_date) {
            return response()->json([
                'message' => 'This curriculum term has no start date or end date. Please set it in Curriculum Management first.',
            ], 422);
        }

        $termStartDate = $term->start_date;
        $termEndDate = $term->end_date;

        /*
        |--------------------------------------------------------------------------
        | Save only complete rows.
        |--------------------------------------------------------------------------
        */
        $saveableResults = collect($data['results'])
            ->filter(function ($item) {
                return ($item['status'] ?? null) === 'room_assigned';
            })
            ->values();

        if ($saveableResults->isEmpty()) {
            return response()->json([
                'message' => 'No complete draft rows to save. Only rows with Room Assigned status can be saved.',
            ], 422);
        }

        $saveReservations = [];

        try {
            $savedCount = DB::transaction(function () use (
                $saveableResults,
                $sectionId,
                $term,
                $termStartDate,
                $termEndDate,
                $user,
                &$saveReservations
            ) {
                $savedSubjects = 0;

                foreach ($saveableResults as $item) {
                    $curriculumTermSubjectId = (int) ($item['curriculum_term_subject_id'] ?? 0);
                    $assignedTeacherId = (int) data_get($item, 'assigned_teacher.id');

                    if (!$curriculumTermSubjectId) {
                        throw new RuntimeException('A draft row is missing curriculum term subject ID.');
                    }

                    if (!$assignedTeacherId) {
                        throw new RuntimeException('A draft row is missing assigned teacher.');
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Confirm subject belongs to the current curriculum term.
                    |--------------------------------------------------------------------------
                    */
                    $curriculumSubject = DB::table('curriculum_term_subjects')
                        ->where('id', $curriculumTermSubjectId)
                        ->where('curriculum_term_id', $term->id)
                        ->first();

                    if (!$curriculumSubject) {
                        throw new RuntimeException('A draft subject does not belong to the current curriculum term.');
                    }

                    $meetings = collect($item['meetings'] ?? []);

                    if ($meetings->isEmpty()) {
                        throw new RuntimeException('A draft row has no meetings.');
                    }

                    $classOfferingId = $item['class_offering_id'] ?? null;
                    $ignoreClassOfferingId = $item['ignore_class_offering_id'] ?? null;

                    $isReschedule = ($item['mode'] ?? null) === 'reschedule'
                        && !empty($classOfferingId);

                    /*
                    |--------------------------------------------------------------------------
                    | Re-check conflicts before saving.
                    |--------------------------------------------------------------------------
                    | If rescheduling, we ignore this offering's current meetings.
                    | Then after validation, we fully delete and replace them.
                    */
                    foreach ($meetings as $meeting) {
                        $dayOfWeek = (int) ($meeting['day_of_week'] ?? 0);
                        $timeStart = $meeting['time_start'] ?? null;
                        $timeEnd = $meeting['time_end'] ?? null;
                        $roomId = (int) ($meeting['room_id'] ?? 0);

                        if (!$dayOfWeek || !$timeStart || !$timeEnd || !$roomId) {
                            throw new RuntimeException('A meeting is missing day, time, or room.');
                        }

                        if ($this->hasSaveDatabaseConflict(
                            dayOfWeek: $dayOfWeek,
                            timeStart: $timeStart,
                            timeEnd: $timeEnd,
                            teacherId: $assignedTeacherId,
                            sectionId: (int) $sectionId,
                            roomId: $roomId,
                            termStartDate: $termStartDate,
                            termEndDate: $termEndDate,
                            ignoreClassOfferingId: $ignoreClassOfferingId ? (int) $ignoreClassOfferingId : null
                        )) {
                            throw new RuntimeException(
                                'Conflict detected before saving. Please regenerate the draft and try again.'
                            );
                        }

                        if ($this->hasSaveDraftConflict(
                            dayOfWeek: $dayOfWeek,
                            timeStart: $timeStart,
                            timeEnd: $timeEnd,
                            teacherId: $assignedTeacherId,
                            sectionId: (int) $sectionId,
                            roomId: $roomId,
                            saveReservations: $saveReservations
                        )) {
                            throw new RuntimeException(
                                'Conflict detected inside the draft being saved. Please regenerate the draft.'
                            );
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Create or update class offering.
                    |--------------------------------------------------------------------------
                    */
                    if ($isReschedule) {
                        $offering = DB::table('class_offerings')
                            ->where('id', $classOfferingId)
                            ->where('section_id', $sectionId)
                            ->lockForUpdate()
                            ->first();

                        if (!$offering) {
                            throw new RuntimeException('Existing class offering was not found for reschedule.');
                        }

                        DB::table('class_offerings')
                            ->where('id', $offering->id)
                            ->update([
                                'curriculum_term_subject_id' => $curriculumTermSubjectId,
                                'start_date' => $termStartDate,
                                'end_date' => $termEndDate,
                                'status' => 'active',
                                'archived_at' => null,
                                'updated_by' => $user->id,
                                'updated_at' => now(),
                            ]);

                        $savedClassOfferingId = (int) $offering->id;
                    } else {
                        $existingActiveOffering = DB::table('class_offerings')
                            ->where('section_id', $sectionId)
                            ->where('curriculum_term_subject_id', $curriculumTermSubjectId)
                            ->where('status', 'active')
                            ->whereDate('start_date', '<=', $termEndDate)
                            ->whereDate('end_date', '>=', $termStartDate)
                            ->lockForUpdate()
                            ->first();

                        if ($existingActiveOffering) {
                            throw new RuntimeException(
                                'An active offering already exists for one of the selected subjects. Regenerate it as reschedule.'
                            );
                        }

                        $savedClassOfferingId = DB::table('class_offerings')
                            ->insertGetId([
                                'section_id' => $sectionId,
                                'curriculum_term_subject_id' => $curriculumTermSubjectId,
                                'start_date' => $termStartDate,
                                'end_date' => $termEndDate,
                                'status' => 'active',
                                'archived_at' => null,
                                'created_by' => $user->id,
                                'updated_by' => $user->id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | FULLY DELETE old meetings.
                    |--------------------------------------------------------------------------
                    | This is the important change you requested.
                    |
                    | We do NOT soft-delete here.
                    | We remove the old schedule rows completely, then insert the new ones.
                    */
                    DB::table('class_meetings')
                        ->where('class_offering_id', $savedClassOfferingId)
                        ->delete();

                    /*
                    |--------------------------------------------------------------------------
                    | Insert new meetings.
                    |--------------------------------------------------------------------------
                    */
                    foreach ($meetings as $meeting) {
                        $dayOfWeek = (int) $meeting['day_of_week'];
                        $timeStart = $meeting['time_start'];
                        $timeEnd = $meeting['time_end'];
                        $roomId = (int) $meeting['room_id'];

                        DB::table('class_meetings')->insert([
                            'class_offering_id' => $savedClassOfferingId,
                            'day_of_week' => $dayOfWeek,
                            'time_start' => $timeStart,
                            'time_end' => $timeEnd,
                            'teacher_id' => $assignedTeacherId,
                            'room_id' => $roomId,
                            'created_by' => $user->id,
                            'updated_by' => $user->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                            'deleted_at' => null,
                        ]);

                        $this->reserveSaveDraftSlot(
                            saveReservations: $saveReservations,
                            dayOfWeek: $dayOfWeek,
                            timeStart: $timeStart,
                            timeEnd: $timeEnd,
                            teacherId: $assignedTeacherId,
                            sectionId: (int) $sectionId,
                            roomId: $roomId
                        );
                    }

                    $savedSubjects++;
                }

                return $savedSubjects;
            });

            return response()->json([
                'message' => "Draft saved successfully. {$savedCount} subject(s) saved.",
                'saved_count' => $savedCount,
                'skipped_count' => count($data['results']) - $savedCount,
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Something went wrong while saving the draft schedule.',
            ], 500);
        }
    }

    private function manualReviewResult(array $offering, string $reason): array
    {
        return [
            'curriculum_term_subject_id' => (int) $offering['curriculum_term_subject_id'],
            'subject_id' => (int) $offering['subject_id'],
            'subject_code' => $offering['subject_code'],
            'subject_name' => $offering['subject_name'],
            'mode' => 'manual_review',
            'status' => 'manual_review',
            'reason' => $reason,
            'assigned_teacher' => null,
            'meetings' => [],
            'possible_slots' => [],
        ];
    }

    private function generateTeacherTimeSlots(int $teacherId, int $durationMinutes): array
    {
        $dayOrder = $this->dayOrderMap();

        $availabilityRows = DB::table('teacher_availabilities')
            ->where('user_id', $teacherId)
            ->whereRaw(
                'TIMESTAMPDIFF(MINUTE, start_time, end_time) >= ?',
                [$durationMinutes]
            )
            ->orderByRaw("FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')")
            ->orderBy('start_time')
            ->get();

        $slots = [];

        foreach ($availabilityRows as $availability) {
            $dayName = $availability->day;

            if (!isset($dayOrder[$dayName])) {
                continue;
            }

            $currentStart = Carbon::createFromFormat('H:i:s', $availability->start_time);
            $availabilityEnd = Carbon::createFromFormat('H:i:s', $availability->end_time);

            while (true) {
                $currentEnd = $currentStart->copy()->addMinutes($durationMinutes);

                if ($currentEnd->greaterThan($availabilityEnd)) {
                    break;
                }

                $slots[] = [
                    'day_name' => $dayName,
                    'day_of_week' => $dayOrder[$dayName],
                    'time_start' => $currentStart->format('H:i:s'),
                    'time_end' => $currentEnd->format('H:i:s'),
                    'duration_minutes' => $durationMinutes,
                ];

                $currentStart = $currentEnd->copy();
            }
        }

        return $slots;
    }

    private function pickNonConflictingSlotsWithRoomsForMeetings(
        array $possibleSlots,
        int $meetingCount,
        int $teacherId,
        int $sectionId,
        ?int $ignoreClassOfferingId,
        array &$draftReservations,
        array $avoidSlots = []
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Phase 10B: try a different schedule first
        |--------------------------------------------------------------------------
        | First attempt: avoid the row's current schedule.
        | Fallback: if no complete alternative exists, allow the old schedule again.
        */
        $originalReservations = $draftReservations;

        $workingReservations = $originalReservations;
        $selected = $this->pickSlotsFromPossibleSlots(
            $possibleSlots,
            $meetingCount,
            $teacherId,
            $sectionId,
            $ignoreClassOfferingId,
            $workingReservations,
            $avoidSlots
        );

        if (count($selected) >= $meetingCount || empty($avoidSlots)) {
            $draftReservations = $workingReservations;
            return $selected;
        }

        $workingReservations = $originalReservations;
        $selected = $this->pickSlotsFromPossibleSlots(
            $possibleSlots,
            $meetingCount,
            $teacherId,
            $sectionId,
            $ignoreClassOfferingId,
            $workingReservations,
            []
        );

        $draftReservations = $workingReservations;
        return $selected;
    }

    private function pickSlotsFromPossibleSlots(
        array $possibleSlots,
        int $meetingCount,
        int $teacherId,
        int $sectionId,
        ?int $ignoreClassOfferingId,
        array &$draftReservations,
        array $avoidSlots = []
    ): array {
        $selected = [];
        $usedDays = [];

        foreach ($possibleSlots as $slot) {
            if (count($selected) >= $meetingCount) {
                break;
            }

            $dayName = $slot['day_name'];

            if (in_array($dayName, $usedDays, true)) {
                continue;
            }

            if ($this->slotMatchesAvoidSlot($slot, $teacherId, $sectionId, $avoidSlots)) {
                continue;
            }

            $slotWithRoom = $this->prepareSlotWithAvailableRoom(
                $slot,
                $teacherId,
                $sectionId,
                $ignoreClassOfferingId,
                $draftReservations
            );

            if (!$slotWithRoom) {
                continue;
            }

            $selected[] = $slotWithRoom;
            $usedDays[] = $dayName;

            $this->reserveDraftSlot($draftReservations, $slotWithRoom, $teacherId, $sectionId);
        }

        foreach ($possibleSlots as $slot) {
            if (count($selected) >= $meetingCount) {
                break;
            }

            if ($this->slotAlreadySelected($selected, $slot)) {
                continue;
            }

            if ($this->slotMatchesAvoidSlot($slot, $teacherId, $sectionId, $avoidSlots)) {
                continue;
            }

            $slotWithRoom = $this->prepareSlotWithAvailableRoom(
                $slot,
                $teacherId,
                $sectionId,
                $ignoreClassOfferingId,
                $draftReservations
            );

            if (!$slotWithRoom) {
                continue;
            }

            $selected[] = $slotWithRoom;

            $this->reserveDraftSlot($draftReservations, $slotWithRoom, $teacherId, $sectionId);
        }

        return $selected;
    }

    private function slotMatchesAvoidSlot(
        array $slot,
        int $teacherId,
        int $sectionId,
        array $avoidSlots
    ): bool {
        foreach ($avoidSlots as $avoidSlot) {
            if ((int) $avoidSlot['day_of_week'] !== (int) $slot['day_of_week']) {
                continue;
            }

            $overlaps =
                $avoidSlot['time_start'] < $slot['time_end'] &&
                $avoidSlot['time_end'] > $slot['time_start'];

            if (!$overlaps) {
                continue;
            }

            $avoidTeacherId = $avoidSlot['teacher_id'] ?? null;
            $avoidSectionId = $avoidSlot['section_id'] ?? null;

            if ($avoidTeacherId && (int) $avoidTeacherId === $teacherId) {
                return true;
            }

            if ($avoidSectionId && (int) $avoidSectionId === $sectionId) {
                return true;
            }
        }

        return false;
    }

    private function prepareSlotWithAvailableRoom(
        array $slot,
        int $teacherId,
        int $sectionId,
        ?int $ignoreClassOfferingId,
        array $draftReservations
    ): ?array {
        if ($this->slotHasTeacherOrSectionConflict(
            $slot,
            $teacherId,
            $sectionId,
            $ignoreClassOfferingId,
            $draftReservations
        )) {
            return null;
        }

        $room = $this->findAvailableRoomForSlot(
            $slot,
            $ignoreClassOfferingId,
            $draftReservations
        );

        if (!$room) {
            return null;
        }

        $slot['room_id'] = (int) $room->id;
        $slot['room_name'] = $room->name;
        $slot['room_capacity'] = (int) $room->capacity;

        return $slot;
    }

    private function findAvailableRoomForSlot(
        array $slot,
        ?int $ignoreClassOfferingId,
        array $draftReservations
    ): ?object {
        $rooms = DB::table('rooms')
            ->where('status', 'available')
            ->where('daily_start_time', '<=', $slot['time_start'])
            ->where('daily_end_time', '>=', $slot['time_end'])
            ->whereNotNull('name')
            ->whereRaw("TRIM(name) != ''")
            ->whereNotIn('name', ['-', '--', 'N/A', 'None', 'TBA', 'TBD'])
            ->orderBy('name')
            ->get();

        foreach ($rooms as $room) {
            $roomId = (int) $room->id;

            if ($this->hasExistingRoomConflict(
                (int) $slot['day_of_week'],
                $slot['time_start'],
                $slot['time_end'],
                $roomId,
                $ignoreClassOfferingId
            )) {
                continue;
            }

            if ($this->hasDraftRoomConflict(
                (int) $slot['day_of_week'],
                $slot['time_start'],
                $slot['time_end'],
                $roomId,
                $draftReservations
            )) {
                continue;
            }

            return $room;
        }

        return null;
    }

    private function slotHasTeacherOrSectionConflict(
        array $slot,
        int $teacherId,
        int $sectionId,
        ?int $ignoreClassOfferingId,
        array $draftReservations
    ): bool {
        if ($this->hasExistingTeacherOrSectionConflict(
            (int) $slot['day_of_week'],
            $slot['time_start'],
            $slot['time_end'],
            $teacherId,
            $sectionId,
            $ignoreClassOfferingId
        )) {
            return true;
        }

        return $this->hasDraftTeacherOrSectionConflict(
            (int) $slot['day_of_week'],
            $slot['time_start'],
            $slot['time_end'],
            $teacherId,
            $sectionId,
            $draftReservations
        );
    }

    private function hasExistingTeacherOrSectionConflict(
        int $dayOfWeek,
        string $timeStart,
        string $timeEnd,
        int $teacherId,
        int $sectionId,
        ?int $ignoreClassOfferingId
    ): bool {
        $today = now()->toDateString();

        $baseQuery = DB::table('class_meetings as cm')
            ->join('class_offerings as co', 'co.id', '=', 'cm.class_offering_id')
            ->whereNull('cm.deleted_at')
            ->where('co.status', 'active')
            ->whereDate('co.end_date', '>', $today)
            ->where('cm.day_of_week', $dayOfWeek)
            ->where('cm.time_start', '<', $timeEnd)
            ->where('cm.time_end', '>', $timeStart)
            ->when($ignoreClassOfferingId, function ($query) use ($ignoreClassOfferingId) {
                $query->where('co.id', '!=', $ignoreClassOfferingId);
            });

        $teacherConflict = (clone $baseQuery)
            ->where('cm.teacher_id', $teacherId)
            ->exists();

        if ($teacherConflict) {
            return true;
        }

        $sectionConflict = (clone $baseQuery)
            ->where('co.section_id', $sectionId)
            ->exists();

        return $sectionConflict;
    }

    private function hasExistingRoomConflict(
        int $dayOfWeek,
        string $timeStart,
        string $timeEnd,
        int $roomId,
        ?int $ignoreClassOfferingId
    ): bool {
        $today = now()->toDateString();

        return DB::table('class_meetings as cm')
            ->join('class_offerings as co', 'co.id', '=', 'cm.class_offering_id')
            ->whereNull('cm.deleted_at')
            ->where('co.status', 'active')
            ->whereDate('co.end_date', '>', $today)
            ->where('cm.day_of_week', $dayOfWeek)
            ->where('cm.time_start', '<', $timeEnd)
            ->where('cm.time_end', '>', $timeStart)
            ->where('cm.room_id', $roomId)
            ->when($ignoreClassOfferingId, function ($query) use ($ignoreClassOfferingId) {
                $query->where('co.id', '!=', $ignoreClassOfferingId);
            })
            ->exists();
    }

    private function hasDraftTeacherOrSectionConflict(
        int $dayOfWeek,
        string $timeStart,
        string $timeEnd,
        int $teacherId,
        int $sectionId,
        array $draftReservations
    ): bool {
        foreach ($draftReservations as $reserved) {
            if ((int) $reserved['day_of_week'] !== $dayOfWeek) {
                continue;
            }

            $overlaps =
                $reserved['time_start'] < $timeEnd &&
                $reserved['time_end'] > $timeStart;

            if (!$overlaps) {
                continue;
            }

            if ((int) $reserved['teacher_id'] === $teacherId) {
                return true;
            }

            if ((int) $reserved['section_id'] === $sectionId) {
                return true;
            }
        }

        return false;
    }

    private function hasDraftRoomConflict(
        int $dayOfWeek,
        string $timeStart,
        string $timeEnd,
        int $roomId,
        array $draftReservations
    ): bool {
        foreach ($draftReservations as $reserved) {
            if ((int) $reserved['day_of_week'] !== $dayOfWeek) {
                continue;
            }

            $overlaps =
                $reserved['time_start'] < $timeEnd &&
                $reserved['time_end'] > $timeStart;

            if (!$overlaps) {
                continue;
            }

            if (!empty($reserved['room_id']) && (int) $reserved['room_id'] === $roomId) {
                return true;
            }
        }

        return false;
    }

    private function hasSaveDatabaseConflict(
        int $dayOfWeek,
        string $timeStart,
        string $timeEnd,
        int $teacherId,
        int $sectionId,
        int $roomId,
        string $termStartDate,
        string $termEndDate,
        ?int $ignoreClassOfferingId = null
    ): bool {
        $baseQuery = DB::table('class_meetings as cm')
            ->join('class_offerings as co', 'co.id', '=', 'cm.class_offering_id')
            ->whereNull('cm.deleted_at')
            ->where('co.status', 'active')
            ->whereDate('co.start_date', '<=', $termEndDate)
            ->whereDate('co.end_date', '>=', $termStartDate)
            ->where('cm.day_of_week', $dayOfWeek)
            ->where('cm.time_start', '<', $timeEnd)
            ->where('cm.time_end', '>', $timeStart)
            ->when($ignoreClassOfferingId, function ($query) use ($ignoreClassOfferingId) {
                $query->where('co.id', '!=', $ignoreClassOfferingId);
            });

        $teacherConflict = (clone $baseQuery)
            ->where('cm.teacher_id', $teacherId)
            ->exists();

        if ($teacherConflict) {
            return true;
        }

        $sectionConflict = (clone $baseQuery)
            ->where('co.section_id', $sectionId)
            ->exists();

        if ($sectionConflict) {
            return true;
        }

        $roomConflict = (clone $baseQuery)
            ->where('cm.room_id', $roomId)
            ->exists();

        return $roomConflict;
    }

    private function hasSaveDraftConflict(
        int $dayOfWeek,
        string $timeStart,
        string $timeEnd,
        int $teacherId,
        int $sectionId,
        int $roomId,
        array $saveReservations
    ): bool {
        foreach ($saveReservations as $reserved) {
            if ((int) $reserved['day_of_week'] !== $dayOfWeek) {
                continue;
            }

            $overlaps =
                $reserved['time_start'] < $timeEnd &&
                $reserved['time_end'] > $timeStart;

            if (!$overlaps) {
                continue;
            }

            if ((int) $reserved['teacher_id'] === $teacherId) {
                return true;
            }

            if ((int) $reserved['section_id'] === $sectionId) {
                return true;
            }

            if ((int) $reserved['room_id'] === $roomId) {
                return true;
            }
        }

        return false;
    }

    private function reserveDraftSlot(
        array &$draftReservations,
        array $slot,
        int $teacherId,
        int $sectionId
    ): void {
        $draftReservations[] = [
            'teacher_id' => $teacherId,
            'section_id' => $sectionId,
            'room_id' => $slot['room_id'] ?? null,
            'day_of_week' => $slot['day_of_week'],
            'time_start' => $slot['time_start'],
            'time_end' => $slot['time_end'],
        ];
    }

    private function reserveSaveDraftSlot(
        array &$saveReservations,
        int $dayOfWeek,
        string $timeStart,
        string $timeEnd,
        int $teacherId,
        int $sectionId,
        int $roomId
    ): void {
        $saveReservations[] = [
            'day_of_week' => $dayOfWeek,
            'time_start' => $timeStart,
            'time_end' => $timeEnd,
            'teacher_id' => $teacherId,
            'section_id' => $sectionId,
            'room_id' => $roomId,
        ];
    }

    private function slotAlreadySelected(array $selectedSlots, array $newSlot): bool
    {
        foreach ($selectedSlots as $slot) {
            if (
                $slot['day_name'] === $newSlot['day_name'] &&
                $slot['time_start'] === $newSlot['time_start'] &&
                $slot['time_end'] === $newSlot['time_end']
            ) {
                return true;
            }
        }

        return false;
    }

    private function dayOrderMap(): array
    {
        return [
            'Monday' => 1,
            'Tuesday' => 2,
            'Wednesday' => 3,
            'Thursday' => 4,
            'Friday' => 5,
            'Saturday' => 6,
            'Sunday' => 7,
        ];
    }

    
}
