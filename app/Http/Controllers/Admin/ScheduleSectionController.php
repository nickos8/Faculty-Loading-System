<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\ClassMeeting;
use App\Models\ClassOffering;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;
use App\Models\Room;
use App\Models\StudentAcademic;
use App\Models\StudentCurriculumSubject;
use App\Models\User;
use App\Models\ClassOfferingFinalization;
use Barryvdh\DomPDF\Facade\Pdf;







class ScheduleSectionController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $programId = $user->program_id; // nullable for super admin

        // Guard: if the user isn't tied to a program, show empty list rather than error
        if (!$programId) {
            return view('admin.schedules.sections.index', [
                'sections' => collect(),
                'program'  => null,
            ]);
        }

        // Pull only ACTIVE sections for this user's program.
        // Also ensure the Program is active and the Curriculum is active.
        // Columns per your schema:
        // sections: id, name, year_level, term_no, program_id, curriculum_id, status
        // programs: id, program_name, status
        // curricula: id, code, title, is_active
        $sections = DB::table('sections as s')
            ->join('programs as p', 'p.id', '=', 's.program_id')
            ->join('curricula as c', 'c.id', '=', 's.curriculum_id')
            ->where('s.program_id', $programId)
            ->where('s.status', 'active')
            ->where('p.status', 'active')
            ->where('c.is_active', 1)
            ->orderBy('s.year_level')
            ->orderBy('s.term_no')
            ->orderBy('s.name')
            ->select([
                's.id',
                's.name as section_name',
                's.year_level',
                's.term_no',
                'p.program_name',
                'c.code as curriculum_code',
            ])
            ->get();

        // Also fetch the program row (for header)
        $program = DB::table('programs')->where('id', $programId)->first();

        return view('admin.schedules.sections.index', compact('sections', 'program'));
    }

    public function students(Request $request, $sectionId)
{
    $user = Auth::user();

    // 1) Load section + program/curriculum context
    $section = DB::table('sections as s')
    ->join('programs as p', 'p.id', '=', 's.program_id')
    ->join('curricula as c', 'c.id', '=', 's.curriculum_id')
    ->where('s.id', $sectionId)
    ->select([
        's.*',
        'p.program_name',
        'p.duration as program_duration',
        'p.terms_per_year',
        'c.code as curriculum_code',
    ])
    ->first();


    abort_if(!$section, 404);

    // Same program guard as in show()
    if ($user->program_id && $user->program_id !== $section->program_id) {
        abort(403);
    }

    // 2) Students in this section, role_id = 4 (student)
    $students = DB::table('student_academics as sa')
        ->join('users as u', 'u.id', '=', 'sa.user_id')
        ->join('user_roles as ur', function ($join) {
            $join->on('ur.user_id', '=', 'u.id')
                 ->where('ur.role_id', 4);
        })
        ->where('sa.section_id', $sectionId)
        ->select([
            'sa.id AS academic_id',
            'u.id AS user_id',
            'u.first_name',
            'u.last_name',
            'u.email',
            'u.school_id',
            'u.gender',
            'sa.status AS academic_status',        // regular / irregular
            'sa.enrollment_status',                // enrolled / dropped / graduated
        ])
        ->orderBy('u.last_name')
        ->orderBy('u.first_name')
        ->get();

    // 3) Candidate sections for transfer:
    // same program, same curriculum, same year, same term, active, not this one
    $candidateSections = DB::table('sections')
        ->where('program_id', $section->program_id)
        ->where('curriculum_id', $section->curriculum_id)
        ->where('year_level', $section->year_level)
        ->where('term_no', $section->term_no)
        ->where('status', 'active')
        ->where('id', '!=', $section->id)
        ->orderBy('name')
        ->get();

    // Enum values from schema
    $enrollmentStatuses = ['enrolled', 'dropped', 'graduated'];

    return view('admin.schedules.sections.students', [
        'section'            => $section,
        'students'           => $students,
        'candidateSections'  => $candidateSections,
        'enrollmentStatuses' => $enrollmentStatuses,
    ]);
}

public function batchUpdateStudents(Request $request, $sectionId)
{
    $user = Auth::user();

    // Reload section with program info
    $section = DB::table('sections as s')
        ->join('programs as p', 'p.id', '=', 's.program_id')
        ->join('curricula as c', 'c.id', '=', 's.curriculum_id')
        ->where('s.id', $sectionId)
        ->select([
            's.*',
            'p.program_name',
            'p.duration as program_duration',
            'p.terms_per_year',
            'c.code as curriculum_code',
        ])
        ->first();

    abort_if(!$section, 404);

    if ($user->program_id && $user->program_id !== $section->program_id) {
        abort(403);
    }

    $data = $request->validate([
        'academic_ids'       => ['required', 'array'],
        'academic_ids.*'     => ['integer'],
        'target_section_id'  => ['nullable', 'integer', 'exists:sections,id'],
        'enrollment_status'  => ['nullable', 'in:enrolled,dropped,graduated'],
    ]);

    $academicIds     = $data['academic_ids'];
    $targetSectionId = $data['target_section_id'] ?? null;
    $newStatus       = $data['enrollment_status'] ?? null;

    if (!$targetSectionId && !$newStatus) {
        throw ValidationException::withMessages([
            'target_section_id' => 'Select a new section and/or a new enrollment status.',
        ]);
    }

    // If target section is provided, enforce same program/year/term
    $effectiveSection = $section;

    if ($targetSectionId) {
        $targetSection = DB::table('sections')->where('id', $targetSectionId)->first();

        if (!$targetSection) {
            abort(404);
        }

        $sameProgram = $targetSection->program_id == $section->program_id;
        $sameCurr    = $targetSection->curriculum_id == $section->curriculum_id;
        $sameYear    = $targetSection->year_level == $section->year_level;
        $sameTerm    = $targetSection->term_no == $section->term_no;

        if (!$sameProgram || !$sameCurr || !$sameYear || !$sameTerm) {
            throw ValidationException::withMessages([
                'target_section_id' => 'Target section must be in the same program, year level, and term as the current section.',
            ]);
        }

        // If a different section is selected, use it to validate "graduated"
        if ($targetSectionId != $section->id) {
            $effectiveSection = $targetSection;
        } else {
            // no actual transfer
            $targetSectionId = null;
        }
    }

    // If user chooses "graduated", only allow on the last year & term of the program
    if ($newStatus === 'graduated') {
        // program_duration & terms_per_year come from the current section's program
        $lastYear = $section->program_duration;
        $lastTerm = $section->terms_per_year;

        $isLastYear = (int) $effectiveSection->year_level === (int) $lastYear;
        $isLastTerm = (int) $effectiveSection->term_no === (int) $lastTerm;

        if (!$isLastYear || !$isLastTerm) {
            throw ValidationException::withMessages([
                'enrollment_status' => 'Graduated status can only be set in the final year and final term of the program.',
            ]);
        }
    }

    $updateData = [];
    if ($targetSectionId) {
        $updateData['section_id'] = $targetSectionId;
    }
    if ($newStatus) {
        $updateData['enrollment_status'] = $newStatus;
    }

    if (empty($updateData)) {
        return back()->with('status', 'No changes applied.');
    }

    $updateData['updated_at'] = now();

    DB::table('student_academics')
        ->where('section_id', $sectionId)
        ->whereIn('id', $academicIds)
        ->update($updateData);

    return back()->with('status', 'Selected students have been updated.');
}




    public function show(Request $request, $sectionId)
    {
        $user = Auth::user();

        // 1) Load section + program/curriculum context
        $section = DB::table('sections as s')
            ->join('programs as p', 'p.id', '=', 's.program_id')
            ->join('curricula as c', 'c.id', '=', 's.curriculum_id')
            ->where('s.id', $sectionId)
            ->select([
                's.*',
                'p.program_name',
                'c.code as curriculum_code',
            ])->first();

        abort_if(!$section, 404);

        // (Optional) program scoping if you restrict admins
        if ($user->program_id && $user->program_id !== $section->program_id) {
            abort(403);
        }

        // 2) Find the regular curriculum term for this section’s (curriculum, year_level, term_no)
        $term = DB::table('curriculum_terms as ct')
            ->where('ct.curriculum_id', $section->curriculum_id)
            ->where('ct.year_level', $section->year_level)
            ->where('ct.term_no', $section->term_no)
            ->where('ct.term_type', 'regular')
            ->orderBy('ct.sequence')
            ->first();

        $requiredSubjects = DB::table('curriculum_term_subjects as cts')
    ->join('subjects as s', 's.id', '=', 'cts.subject_id')
    ->leftJoin('class_offerings as co', function ($j) use ($section) {
        $j->on('co.curriculum_term_subject_id', '=', 'cts.id')
          ->where('co.section_id', '=', $section->id)
          ->where('co.status', '=', 'active');
    })
    ->leftJoin('class_meetings as cm', 'cm.class_offering_id', '=', 'co.id')
    ->where('cts.curriculum_term_id', $term->id)
    ->where('cts.is_required', 1)
    ->where('s.status', 'active')
    ->groupBy('cts.id', 's.code', 's.name', 'cts.unit', 'cts.type')
    ->select([
        'cts.id as cts_id',
        's.code',
        's.name',
        DB::raw('cts.unit as units'),
        DB::raw('cts.type as type'),
        DB::raw('MIN(co.start_date) as offering_start_date'),
        DB::raw('MAX(co.end_date)   as offering_end_date'),
        DB::raw('COALESCE(SUM(TIMESTAMPDIFF(MINUTE, cm.time_start, cm.time_end)), 0) as scheduled_minutes'),
        DB::raw('(cts.unit * 60) as required_minutes'),
        DB::raw('((cts.unit * 60) - COALESCE(SUM(TIMESTAMPDIFF(MINUTE, cm.time_start, cm.time_end)), 0)) as remaining_minutes'),
    ])
    ->havingRaw('((cts.unit * 60) - COALESCE(SUM(TIMESTAMPDIFF(MINUTE, cm.time_start, cm.time_end)), 0)) > 0')
    ->orderBy('s.code')
    ->get();



        // 4) Existing active offerings (current or future), with meetings
       $offerings = ClassOffering::query()
    ->where('section_id', $sectionId)
    ->where('status', 'active')
    ->whereDate('end_date', '>=', now()->toDateString())
    ->with([
        'curriculumTermSubject.subject',
        'meetings' => fn($q) => $q->orderBy('day_of_week')->orderBy('time_start'),
        'meetings.teacher',
        'meetings.room',
    ])
    ->orderBy('start_date')
    ->get();

    $scheduleRows = $offerings
    ->flatMap(function ($off) {
        $subj = $off->curriculumTermSubject?->subject;

        return $off->meetings->map(function ($m) use ($off, $subj) {
            return (object) [
                'off' => $off,
                'm'   => $m,
                'subj_code' => $subj?->code,
                'subj_name' => $subj?->name,
            ];
        });
    })
    ->sortBy(function ($r) {
        // Day asc, Time asc, Subject code asc
        return sprintf('%02d|%s|%s', (int)$r->m->day_of_week, $r->m->time_start, $r->subj_code ?? '');
    })
    ->values();






        return view('admin.schedules.sections.show', compact(
    'section','term','requiredSubjects','offerings','scheduleRows'
));

    }

public function downloadPdf(Request $request, $sectionId)
{
    $user = Auth::user();

    // 1) Same section context as show()
    $section = DB::table('sections as s')
        ->join('programs as p', 'p.id', '=', 's.program_id')
        ->join('curricula as c', 'c.id', '=', 's.curriculum_id')
        ->where('s.id', $sectionId)
        ->select([
            's.*',
            'p.program_name',
            'c.code as curriculum_code',
        ])->first();

    abort_if(!$section, 404);

    // program scoping guard (same logic as show)
    if ($user->program_id && $user->program_id !== $section->program_id) {
        abort(403);
    }

    $today = now()->toDateString();

    // 2) Load offerings WITH subject
    $offerings = ClassOffering::query()
        ->where('section_id', $sectionId)
        ->where('status', 'active')
        ->whereDate('end_date', '>=', $today)
        ->with([
            'curriculumTermSubject.subject',
            'meetings' => fn($q) => $q->orderBy('day_of_week')->orderBy('time_start'),
            'meetings.teacher',
            'meetings.room',
        ])
        ->orderBy('start_date')
        ->get();

    // 3) Flatten meetings for GRID
    $rows = collect();
    foreach ($offerings as $off) {
        $cts  = $off->curriculumTermSubject;
        $subj = $cts?->subject;

        $subjectTitle = $subj?->name ?? '—';
        $subjectCode  = $subj?->code ?? '—';
        $units        = (float) ($cts->unit ?? ($subj->units ?? 0));

        foreach ($off->meetings as $m) {
            $teacherName = trim(($m->teacher->first_name ?? '') . ' ' . ($m->teacher->last_name ?? ''));

            $rows->push([
                'day'          => (int) $m->day_of_week,          // 1=Mon..7=Sun
                'start'        => substr($m->time_start, 0, 5),   // HH:MM
                'end'          => substr($m->time_end, 0, 5),     // HH:MM
                'subject'      => $subjectTitle,
                'subject_code' => $subjectCode,
                'units'        => $units,
                'teacher'      => $teacherName ?: '—',
                'room'         => $m->room->name ?? '—',
            ]);
        }
    }

    // 4) Bottom summary table like screenshot (group per subject)
    $subjectSummary = $rows
        ->groupBy('subject_code')
        ->map(function ($group) {
            $first = $group->first();

            $teachers = $group->pluck('teacher')
                ->filter(fn($t) => $t && $t !== '—')
                ->unique()
                ->values()
                ->implode(', ');

            return [
                'code'        => $first['subject_code'] ?? '—',
                'title'       => $first['subject'] ?? '—',
                'units'       => $first['units'] ?? 0,
                'instructors' => $teachers ?: '—',
            ];
        })
        ->values();

    $days = [
        1=>'MONDAY', 2=>'TUESDAY', 3=>'WEDNESDAY',
        4=>'THURSDAY', 5=>'FRIDAY', 6=>'SATURDAY', 7=>'SUNDAY'
    ];

    $pdf = Pdf::loadView('admin.schedules.sections.pdf', [
        'section'        => $section,
        'rows'           => $rows,
        'days'           => $days,
        'subjectSummary' => $subjectSummary,

        // keep your header variables
        'schoolName'    => 'Granby Colleges Of Science and Technology',
        'schoolAddress' => 'Ibayo silangan, Naic, Cavite, Philippines',
        'schoolContact' => 'Tel: (63) 111-2222 • Email: Granby@gmail.com',
        'termLabel'     => 'School Year: ____________',
    ])->setPaper('a4', 'portrait'); // ✅ screenshot layout

    $filename = 'section_schedule_' . $section->id . '_' . now()->format('Y-m-d') . '.pdf';
    return $pdf->stream($filename);
}




public function storeOffering(Request $request, int $sectionId)
{
    $user = Auth::user();

    $data = $request->validate([
        'curriculum_term_subject_id' => ['required','integer','exists:curriculum_term_subjects,id'],

        // ✅ now optional; server will enforce term dates
        'start_date' =>  ['nullable','date'],
        'end_date'   => ['nullable','date','after_or_equal:start_date'],

        'day_of_week'=> ['required','integer','between:1,7'], // 1=Mon…7=Sun
        'time_start' => ['required','date_format:H:i'],
        'meeting_units' => ['required','numeric','min:0.5'],
        'time_end'   => ['required','date_format:H:i','after:time_start'],
        'teacher_id' => ['required','integer','exists:users,id'],
        'room_id'    => ['required','integer','exists:rooms,id'],
    ]);

    // --- SECTION & TERM ---
    $section = DB::table('sections')->where('id', $sectionId)->first();
    abort_if(!$section, 404);

    $term = DB::table('curriculum_terms as ct')
        ->where('ct.curriculum_id', $section->curriculum_id)
        ->where('ct.year_level', $section->year_level)
        ->where('ct.term_no', $section->term_no)
        ->where('ct.term_type', 'regular')
        ->first();

    if (!$term) {
        return back()->withErrors(['term' => 'No regular curriculum term found for this section.']);
    }

    // ✅ NEW: unified term dates enforced here
    if (empty($term->start_date) || empty($term->end_date)) {
        return back()->withErrors([
            'term' => 'This curriculum term has no Start/End date yet. Please set it in Curriculum Term Management first.'
        ])->withInput();
    }

    $sd = (string) $term->start_date;
    $ed = (string) $term->end_date;

    // --- SUBJECT (CTS) + UNITS ---
    $cts = DB::table('curriculum_term_subjects as cts')
        ->join('curriculum_terms as ct','ct.id','=','cts.curriculum_term_id')
        ->where('cts.id', $data['curriculum_term_subject_id'])
        ->where('cts.curriculum_term_id', $term->id)
        ->select('cts.id', 'cts.unit')
        ->first();

    if (!$cts) {
        return back()->withErrors([
            'curriculum_term_subject_id' => 'Subject is not part of this section’s curriculum term.'
        ]);
    }

    // Total subject units (e.g. 3.0) for the whole offering
    $subjectUnits = (float) $cts->unit;

    // Units for THIS meeting (e.g. 1.5)
    $meetingUnits = (float) $data['meeting_units'];

    // --- RULE: 1 unit (credit hour) = 60 minutes of meeting time ----------------
    $parseMins = function (string $hhmm): int {
        [$h, $m] = array_map('intval', explode(':', $hhmm));
        return $h * 60 + $m;
    };

    $startMins      = $parseMins($data['time_start']);
    $endMins        = $parseMins($data['time_end']);
    $actualMinutes  = $endMins - $startMins;

    $totalRequiredMinutes   = (int) round($subjectUnits * 60);   // total for subject
    $meetingRequiredMinutes = (int) round($meetingUnits * 60);   // this session only

    if ($actualMinutes !== $meetingRequiredMinutes) {
        return back()->withErrors([
            'time_end' => "You selected {$meetingUnits} unit(s) = {$meetingRequiredMinutes} minutes for this meeting, "
                        . "but your time range is {$actualMinutes} minutes. Adjust Start/End or let the form auto-fill End time."
        ])->withInput();
    }

    // --- PREP VALUES ---
    $day     = (int)$data['day_of_week'];
    $dayName = $this->mapDayName($day);
    $ts      = $data['time_start'];
    $te      = $data['time_end'];
    $tid     = (int)$data['teacher_id'];
    $rid     = (int)$data['room_id'];
    $ctsId   = (int)$data['curriculum_term_subject_id'];

    // --- FIND-OR-CREATE OFFERING HEADER ---
    $offering = ClassOffering::query()
        ->where('section_id', $sectionId)
        ->where('curriculum_term_subject_id', $ctsId)
        ->where('status', 'active')
        ->first();

    // ✅ NEW: if offering exists but dates don't match the term, we will sync it in the transaction.
    // Do NOT override $sd/$ed from offering anymore (term is source of truth).

    // SAME-TEACHER POLICY: if offering already has meetings, teacher must match
    if ($offering) {
        $existingTeacher = DB::table('class_meetings')
            ->where('class_offering_id', $offering->id)
            ->orderBy('id')
            ->value('teacher_id');

        if ($existingTeacher && (int)$existingTeacher !== $tid) {
            return back()->withErrors([
                'teacher_id' => 'All sessions for this subject must use the same teacher.'
            ])->withInput();
        }
    }

    // --- RUNNING TOTAL RULE (remaining minutes) ---
    $scheduled = 0;
    if ($offering) {
        $scheduled = (int) DB::table('class_meetings')
            ->where('class_offering_id', $offering->id)
            ->selectRaw('COALESCE(SUM(TIMESTAMPDIFF(MINUTE, time_start, time_end)),0) as mins')
            ->value('mins');
    }

    $remaining = $totalRequiredMinutes - $scheduled;

    if ($meetingRequiredMinutes > $remaining) {
        return back()->withErrors([
            'meeting_units' => "Only {$remaining} minute(s) remaining for this subject."
        ])->withInput();
    }

    // --- AVAILABILITY / ROOM WINDOW (keep as-is) ---
    $teacherHasWindow = DB::table('teacher_availabilities')
        ->where('user_id', $tid)
        ->where('day', $dayName)
        ->where('start_time', '<=', $ts)
        ->where('end_time', '>=', $te)
        ->exists();
    if (!$teacherHasWindow) {
        return back()->withErrors([
            'teacher_id' => "Selected teacher is not available on {$dayName} {$ts}–{$te}."
        ])->withInput();
    }

    $roomOk = DB::table('rooms')
        ->where('id', $rid)
        ->where('status', 'available')
        ->where('daily_start_time', '<=', $ts)
        ->where('daily_end_time', '>=', $te)
        ->exists();
    if (!$roomOk) {
        return back()->withErrors([
            'room_id' => 'Selected room is not available for this time window.'
        ])->withInput();
    }

    // --- TEACHER MAX LOAD (server-side enforcement) ---
    // Charge the teacher the subject units ONCE per offering (not per meeting).
    // Only validate on first meeting creation (when header doesn't exist yet).
    if (!$offering) {
        $this->assertTeacherWithinMaxLoad($tid, $subjectUnits, null);
    }

    // --- CONFLICTS + CREATE (transaction) ---
    try {
        DB::transaction(function () use ($sectionId, $user, $day, $ts, $te, $sd, $ed, $tid, $rid, $ctsId, &$offering) {

            $overlap = function ($q) use ($day, $ts, $te, $sd, $ed) {
                $q->where('cm.day_of_week', $day)
                  // time overlap: new_start < existing_end AND new_end > existing_start
                  ->whereRaw('? < cm.time_end', [$ts])
                  ->whereRaw('? > cm.time_start', [$te])
                  // date overlap: new_start_date <= existing_end_date AND new_end_date >= existing_start_date
                  ->whereRaw('? <= co.end_date', [$sd])
                  ->whereRaw('? >= co.start_date', [$ed]);
            };

            $teacherClash = DB::table('class_meetings as cm')
                ->join('class_offerings as co', 'co.id', '=', 'cm.class_offering_id')
                ->where('co.status', 'active')
                ->where('cm.teacher_id', $tid)
                ->tap($overlap)
                ->exists();
            if ($teacherClash) {
                throw ValidationException::withMessages([
                    'teacher_id' => 'Teacher has a conflicting class in that window.',
                ]);
            }

            $roomClash = DB::table('class_meetings as cm')
                ->join('class_offerings as co', 'co.id', '=', 'cm.class_offering_id')
                ->where('co.status', 'active')
                ->where('cm.room_id', $rid)
                ->tap($overlap)
                ->exists();
            if ($roomClash) {
                throw ValidationException::withMessages([
                    'room_id' => 'Room is occupied in that window.',
                ]);
            }

            $sectionClash = DB::table('class_meetings as cm')
                ->join('class_offerings as co', 'co.id', '=', 'cm.class_offering_id')
                ->where('co.status', 'active')
                ->where('co.section_id', $sectionId)
                ->tap($overlap)
                ->exists();
            if ($sectionClash) {
                throw ValidationException::withMessages([
                    'day_of_week' => 'This section already has a class in that window.',
                ]);
            }

            // Create header if needed
            if (!$offering) {
                $offering = ClassOffering::create([
                    'section_id'                 => $sectionId,
                    'curriculum_term_subject_id' => $ctsId,
                    'start_date'                 => $sd, // ✅ forced from term
                    'end_date'                   => $ed, // ✅ forced from term
                    'status'                     => 'active',
                    'created_by'                 => $user->id,
                    'updated_by'                 => $user->id,
                ]);
            } else {
                // ✅ NEW: keep existing offering dates synced to the term
                if ((string)$offering->start_date !== (string)$sd || (string)$offering->end_date !== (string)$ed) {
                    $offering->start_date = $sd;
                    $offering->end_date   = $ed;
                    if (Schema::hasColumn('class_offerings', 'updated_by')) {
                        $offering->updated_by = $user->id;
                    }
                    $offering->save();
                }
            }

            $this->syncOfficialCurriculumEnrollmentForSection(
                (int) $sectionId,
                (int) $offering->curriculum_term_subject_id,
                (int) $offering->id,
                true
            );

            ClassMeeting::create([
                'class_offering_id' => $offering->id,
                'day_of_week'       => $day,
                'time_start'        => $ts,
                'time_end'          => $te,
                'teacher_id'        => $tid,
                'room_id'           => $rid,
                'created_by'        => $user->id,
                'updated_by'        => $user->id,
            ]);
        });
    } catch (ValidationException $ve) {
        return back()->withErrors($ve->errors())->withInput();
    }

    return redirect()
        ->route('admin.schedules.sections.show', $sectionId)
        ->with('status', 'Schedule created.');
}




private function mapDayName(int $dow): ?string
{
    // meetings use 1..7; availability uses strings
    $map = [1=>'Monday',2=>'Tuesday',3=>'Wednesday',4=>'Thursday',5=>'Friday',6=>'Saturday',7=>'Sunday'];
    return $map[$dow] ?? null;
}

/** Add time+date overlap filters to a cm/co joined query (half-open [start, end)). */
private function applyOverlap(\Illuminate\Database\Query\Builder $q,
                              int $day, string $tStart, string $tEnd,
                              string $dStart, string $dEnd): void
{
    $q->where('cm.day_of_week', $day)
      // time overlap: new_start < existing_end AND new_end > existing_start
      ->whereRaw('? < cm.time_end', [$tStart])
      ->whereRaw('? > cm.time_start', [$tEnd])
      // date overlap: new_start_date <= existing_end_date AND new_end_date >= existing_start_date
      ->whereRaw('? <= co.end_date', [$dStart])
      ->whereRaw('? >= co.start_date', [$dEnd]);
}

public function availableTeachers(Request $request, int $sectionId)
{
    $data = $request->validate([
        'curriculum_term_subject_id' => ['required','integer'],


        'day_of_week'=> ['required','integer','between:1,7'],
        'time_start' => ['required','date_format:H:i'],
        'time_end'   => ['required','date_format:H:i','after:time_start'],
        'ignore_meeting_id' => ['nullable','integer'],
    ]);

    // --- SECTION & TERM (derive unified dates) ---
    $section = DB::table('sections')->where('id', $sectionId)->first();
    abort_if(!$section, 404);

    $term = DB::table('curriculum_terms as ct')
        ->where('ct.curriculum_id', $section->curriculum_id)
        ->where('ct.year_level', $section->year_level)
        ->where('ct.term_no', $section->term_no)
        ->where('ct.term_type', 'regular')
        ->first();

    if (!$term) {
        return response()->json(['message' => 'No regular curriculum term found for this section.'], 422);
    }

    if (empty($term->start_date) || empty($term->end_date)) {
        return response()->json(['message' => 'Term start/end date is not set yet.'], 422);
    }

    // ✅ enforce unified dates from term
    $sd = (string) $term->start_date;
    $ed = (string) $term->end_date;

    // Optional safety: CTS must belong to this term
    $ctsOk = DB::table('curriculum_term_subjects')
        ->where('id', (int) $data['curriculum_term_subject_id'])
        ->where('curriculum_term_id', $term->id)
        ->exists();

    if (!$ctsOk) {
        return response()->json(['message' => 'Selected subject is not part of this section term.'], 422);
    }

    $ignoreMeetingId = $data['ignore_meeting_id'] ?? null;

    // If editing an existing meeting, exclude its offering from load computations
    $excludeOfferingId = null;
    if ($ignoreMeetingId) {
        $excludeOfferingId = DB::table('class_meetings')->where('id', $ignoreMeetingId)->value('class_offering_id');
    }

    $day = (int) $data['day_of_week'];
    $dayName = $this->mapDayName($day);
    if (!$dayName) {
        return response()->json([], 200);
    }

    $ts = $data['time_start'];
    $te = $data['time_end'];

    // subjectProgramId logic (as you already have)
    $subjectProgramId = DB::table('curriculum_term_subjects as cts')
        ->join('subjects as s', 's.id', '=', 'cts.subject_id')
        ->where('cts.id', (int) $data['curriculum_term_subject_id'])
        ->value('s.program_id');
    $subjectProgramId = $subjectProgramId ?? 0;

    $base = DB::table('users as u')
        ->join('user_roles as ur', 'ur.user_id', '=', 'u.id')
        ->leftJoin('programs as p', 'p.id', '=', 'u.program_id')
        ->where('ur.role_id', 3)
        ->where('u.status', 'active');

    // ✅ availability join stays as-is
    $withAvailability = $base
        ->join('teacher_availabilities as ta', function ($j) use ($dayName, $ts, $te) {
            $j->on('ta.user_id', '=', 'u.id')
              ->where('ta.day', '=', $dayName)
              ->where('ta.start_time', '<=', $ts)
              ->where('ta.end_time', '>=', $te);
        });

    $subjectUnits = (float) DB::table('curriculum_term_subjects')
        ->where('id', (int) $data['curriculum_term_subject_id'])
        ->value('unit');

    // Avoid double-counting units when offering has multiple meetings
    $offeringUnitsSub = DB::table('class_meetings as cm')
        ->join('class_offerings as co', 'co.id', '=', 'cm.class_offering_id')
        ->join('curriculum_term_subjects as cts', 'cts.id', '=', 'co.curriculum_term_subject_id')
        ->where('co.status', 'active')
        ->whereDate('co.end_date', '>=', now()->toDateString())
        ->when($excludeOfferingId, fn($q) => $q->where('co.id', '!=', $excludeOfferingId))
        ->selectRaw('cm.teacher_id, co.id as class_offering_id, cts.unit')
        ->distinct();

    $loadSub = DB::query()
        ->fromSub($offeringUnitsSub, 'x')
        ->groupBy('teacher_id')
        ->selectRaw('teacher_id, SUM(unit) as active_units');

    $filtered = $withAvailability
        ->leftJoinSub($loadSub, 'tl', function ($j) {
            $j->on('tl.teacher_id', '=', 'u.id');
        })
        ->leftJoin('teacher_load_settings as tls', 'tls.user_id', '=', 'u.id')
        ->whereRaw('COALESCE(tls.max_units, 0) > 0')
        ->whereRaw('(COALESCE(tl.active_units, 0) + ?) <= COALESCE(tls.max_units, 0)', [$subjectUnits])

        // conflict overlap filter (✅ now uses term dates)
        ->whereNotExists(function ($sub) use ($day, $ts, $te, $sd, $ed, $ignoreMeetingId) {
            $sub->from('class_meetings as cm')
                ->join('class_offerings as co', 'co.id', '=', 'cm.class_offering_id')
                ->where('co.status', 'active')
                ->whereColumn('cm.teacher_id', 'u.id')
                ->tap(fn($q) => $this->applyOverlap($q, $day, $ts, $te, $sd, $ed));

            if ($ignoreMeetingId) {
                $sub->where('cm.id', '!=', $ignoreMeetingId);
            }

            $sub->limit(1);
        })
        ->distinct()
        ->selectRaw("
            u.id,
            CONCAT(
                u.first_name,' ',u.last_name,
                CASE
                    WHEN p.program_code IS NOT NULL AND p.program_code <> ''
                    THEN CONCAT(' - ', p.program_code)
                    ELSE ''
                END,
                CASE
                    WHEN u.program_id = ? THEN ' (Recommended)' ELSE '' END
            ) as name,
            p.program_code as program_code,
            CASE WHEN u.program_id = ? THEN 1 ELSE 0 END as same_program_priority
        ", [$subjectProgramId, $subjectProgramId])
        ->orderByDesc('same_program_priority')
        ->orderBy('name')
        ->get();

    return response()->json($filtered, 200);
}





public function availableRooms(Request $request, int $sectionId)
{
    $data = $request->validate([
        'curriculum_term_subject_id' => ['required','integer'],

        // ✅ now optional; server will enforce term dates
        'start_date' => ['nullable','date'],
        'end_date'   => ['nullable','date','after_or_equal:start_date'],

        'day_of_week'=> ['required','integer','between:1,7'],
        'time_start' => ['required','date_format:H:i'],
        'time_end'   => ['required','date_format:H:i','after:time_start'],
        'ignore_meeting_id' => ['nullable','integer'],
    ]);

    // --- SECTION & TERM (derive unified dates) ---
    $section = DB::table('sections')->where('id', $sectionId)->first();
    abort_if(!$section, 404);

    $term = DB::table('curriculum_terms as ct')
        ->where('ct.curriculum_id', $section->curriculum_id)
        ->where('ct.year_level', $section->year_level)
        ->where('ct.term_no', $section->term_no)
        ->where('ct.term_type', 'regular')
        ->first();

    if (!$term) {
        return response()->json(['message' => 'No regular curriculum term found for this section.'], 422);
    }

    if (empty($term->start_date) || empty($term->end_date)) {
        return response()->json(['message' => 'Term start/end date is not set yet.'], 422);
    }

    // ✅ enforce unified dates from term
    $sd = (string) $term->start_date;
    $ed = (string) $term->end_date;

    // Optional safety: CTS must belong to this term
    $ctsOk = DB::table('curriculum_term_subjects')
        ->where('id', (int) $data['curriculum_term_subject_id'])
        ->where('curriculum_term_id', $term->id)
        ->exists();

    if (!$ctsOk) {
        return response()->json(['message' => 'Selected subject is not part of this section term.'], 422);
    }

    $ignoreMeetingId = $data['ignore_meeting_id'] ?? null;

    $day = (int) $data['day_of_week'];
    $ts  = $data['time_start'];
    $te  = $data['time_end'];

    $base = DB::table('rooms as r')
        ->where('r.status', 'available')
        ->where('r.daily_start_time', '<=', $ts)
        ->where('r.daily_end_time', '>=', $te);

    $filtered = $base
        ->whereNotExists(function ($sub) use ($day, $ts, $te, $sd, $ed, $ignoreMeetingId) {
            $sub->from('class_meetings as cm')
                ->join('class_offerings as co', 'co.id', '=', 'cm.class_offering_id')
                ->where('co.status', 'active')
                ->whereColumn('cm.room_id', 'r.id')
                ->tap(fn($q) => $this->applyOverlap($q, $day, $ts, $te, $sd, $ed));

            if ($ignoreMeetingId) {
                $sub->where('cm.id', '!=', $ignoreMeetingId);
            }

            $sub->limit(1);
        })
        ->select('r.id','r.name')
        ->orderBy('r.name')
        ->get();

    return response()->json($filtered, 200);
}




public function editOffering(Request $request, int $sectionId, int $offeringId)
{
    // Load the offering + its meeting(s)
    $offering = ClassOffering::with(['meetings.teacher', 'meetings.room'])
        ->where('section_id', $sectionId)
        ->findOrFail($offeringId);

    // NEW: allow editing a specific meeting (for multi-session subjects)
    $meetingId = (int) $request->query('meeting_id', 0);
    $meeting = $meetingId
        ? $offering->meetings->firstWhere('id', $meetingId)
        : $offering->meetings->first();

    abort_if(!$meeting, 404);

    $teachers = User::orderBy('last_name')
        ->orderBy('first_name')
        ->get(['id', 'first_name', 'last_name']);

    $rooms = Room::orderBy('name')->get(['id', 'name']);

    // NOTE: keep this for compatibility (even if the UI no longer depends on it)
    $effectiveUnits = DB::table('curriculum_term_subjects as cts')
        ->where('cts.id', $offering->curriculum_term_subject_id)
        ->value('cts.unit');

    return view('admin.schedules.sections.offerings.edit', [
        'offering'       => $offering,
        'meeting'        => $meeting,
        'teachers'       => $teachers,
        'rooms'          => $rooms,
        'sectionId'      => $sectionId,
        'effectiveUnits' => (float) ($effectiveUnits ?? 0),
    ]);
}


public function updateOffering(Request $request, int $sectionId, int $offeringId)
{
    // 1) Load offering and make sure it belongs to this section
    $offering = ClassOffering::with('meetings')
        ->where('section_id', $sectionId)
        ->findOrFail($offeringId);

    // 2) Validate incoming fields from the edit form
    $data = $request->validate([
        'meeting_id'  => ['required','integer'],
        'start_date'  => ['required','date'],
        'end_date'    => ['required','date','after_or_equal:start_date'],
        'day_of_week' => ['required','integer','between:1,7'],
        'time_start'  => ['required','date_format:H:i'],
        'time_end'    => ['required','date_format:H:i','after:time_start'],
        'teacher_id'  => ['required','integer','exists:users,id'],
        'room_id'     => ['required','integer','exists:rooms,id'],
    ]);

    // Ensure meeting belongs to this offering
    $meetingId = (int) $data['meeting_id'];
    $meeting = $offering->meetings()->where('id', $meetingId)->firstOrFail();

    // 3) Unpack the validated data into clean variables
    $sd  = $data['start_date'];
    $ed  = $data['end_date'];
    $day = (int) $data['day_of_week'];
    $ts  = $data['time_start'];
    $te  = $data['time_end'];
    $tid = (int) $data['teacher_id'];
    $rid = (int) $data['room_id'];

    // 4) Business rules
    $ignoreId = (int) $meeting->id;

    // 4.1) Running total rule: do not exceed total required minutes for this subject
    $this->assertOfferingMinutesNotExceeded($offering->id, $ignoreId, $ts, $te);

    // 4.2) SAME-TEACHER POLICY across meetings (match any other meeting in this offering)
    $otherTeacher = DB::table('class_meetings')
        ->where('class_offering_id', $offering->id)
        ->where('id', '!=', $ignoreId)
        ->orderBy('id')
        ->value('teacher_id');

    if ($otherTeacher && (int) $otherTeacher !== $tid) {
        throw ValidationException::withMessages([
            'teacher_id' => 'All sessions for this subject must use the same teacher.',
        ]);
    }


    // 4.2.1) TEACHER MAX LOAD: if changing teacher, ensure new teacher can take this offering
    $oldTeacherId = (int) $meeting->teacher_id;
    if ($tid !== $oldTeacherId) {
        $subjectUnits = $this->getOfferingUnits($offering->id);
        $this->assertTeacherWithinMaxLoad($tid, $subjectUnits, null);
    }
    // 4.3) Check teacher availability table and room daily window
    $dayName = $this->mapDayName($day);
    $this->assertTeacherAvailable($tid, $dayName, $ts, $te);
    $this->assertRoomAvailable($rid, $ts, $te);

    // 4.4) Conflict checks, ignoring THIS meeting itself
    $this->assertNoTeacherConflicts($tid, $day, $ts, $te, $sd, $ed, $ignoreId);
    $this->assertNoRoomConflicts($rid, $day, $ts, $te, $sd, $ed, $ignoreId);
    $this->assertNoSectionConflicts($offering->section_id, $day, $ts, $te, $sd, $ed, $ignoreId);

    // 5) Save changes atomically
    DB::transaction(function () use ($offering, $meeting, $sd, $ed, $day, $ts, $te, $tid, $rid) {
        // 5.1) Update offering dates
        $offering->start_date = $sd;
        $offering->end_date   = $ed;

        if (Schema::hasColumn('class_offerings', 'updated_by')) {
            $offering->updated_by = Auth::id();
        }

        $offering->save();

        // 5.2) Update meeting details
        $meeting->day_of_week = $day;
        $meeting->time_start  = $ts;
        $meeting->time_end    = $te;
        $meeting->teacher_id  = $tid;
        $meeting->room_id     = $rid;

        if (Schema::hasColumn('class_meetings', 'updated_by')) {
            $meeting->updated_by = Auth::id();
        }

        $meeting->save();
    });

    return redirect()
        ->route('admin.schedules.sections.show', $sectionId)
        ->with('status', 'Schedule updated.');
}

public function unlockOffering(Request $request, int $offeringId)
{
    $request->validate([
        'unlock_reason' => ['required', 'string', 'min:5'],
    ]);

    $user = Auth::user();
    $programId = $user->program_id;

    $offering = ClassOffering::query()
        ->with('section')
        ->findOrFail($offeringId);

    // ✅ Program guard
    if ($programId && (int)$offering->section?->program_id !== (int)$programId) {
        abort(403);
    }

    $finalization = ClassOfferingFinalization::query()
        ->where('class_offering_id', $offering->id)
        ->first();

    if (!$finalization || !$finalization->finalized_at) {
        return back()->withErrors(['unlock_reason' => 'This offering is not finalized yet. Nothing to unlock.']);
    }

    if ($finalization->unlocked_at) {
        return back()->withErrors(['unlock_reason' => 'This offering is already unlocked.']);
    }

    $finalization->update([
        'unlocked_at'    => now(),
        'unlocked_by'    => $user->id,
        'unlock_reason'  => $request->unlock_reason,
    ]);

    return redirect()
        ->route('admin.schedules.offerings.status', $offering->id)
        ->with('success', 'Offering unlocked. Teachers can edit evaluations again.');
}
//update private helpers
// === Validation helpers for offerings/meetings ===

/** Get effective units for an offering (curriculum term subject). */
private function getOfferingUnits(int $offeringId): float
{
    $row = DB::table('class_offerings as co')
        ->join('curriculum_term_subjects as cts', 'cts.id', '=', 'co.curriculum_term_subject_id')
        ->where('co.id', $offeringId)
        ->selectRaw('cts.unit AS eff_units')
        ->first();

    return (float) ($row?->eff_units ?? 0);
}

/** Parse "HH:MM" to total minutes since midnight. */
private function parseMinutes(string $hhmm): int
{
    [$h, $m] = array_pad(explode(':', $hhmm), 2, 0);
    return ((int) $h) * 60 + ((int) $m);
}

/** Total required minutes for the whole subject (units * 60). */
private function getOfferingRequiredMinutes(int $offeringId): int
{
    return (int) round($this->getOfferingUnits($offeringId) * 60);
}

/** Sum of minutes already scheduled for this offering (optionally ignoring one meeting). */
private function getOfferingScheduledMinutes(int $offeringId, ?int $ignoreMeetingId = null): int
{
    $q = DB::table('class_meetings')
        ->where('class_offering_id', $offeringId);

    if ($ignoreMeetingId) {
        $q->where('id', '!=', $ignoreMeetingId);
    }

    return (int) $q->selectRaw('COALESCE(SUM(TIMESTAMPDIFF(MINUTE, time_start, time_end)),0) as mins')
        ->value('mins');
}

/**
 * Ensure the updated meeting does NOT make the offering exceed total required minutes.
 * This supports 2x/3x meetings per week for the same subject.
 */
private function assertOfferingMinutesNotExceeded(int $offeringId, int $ignoreMeetingId, string $timeStart, string $timeEnd): void
{
    $required  = $this->getOfferingRequiredMinutes($offeringId);
    $newMins   = $this->parseMinutes($timeEnd) - $this->parseMinutes($timeStart);
    $otherMins = $this->getOfferingScheduledMinutes($offeringId, $ignoreMeetingId);

    if (($otherMins + $newMins) > $required) {
        $remaining = $required - $otherMins;
        throw ValidationException::withMessages([
            'time_end' => "This change exceeds total required minutes for this subject. Remaining: {$remaining} minute(s).",
        ]);
    }
}



/** Teacher availability containment on a weekday window. */
private function assertTeacherAvailable(int $teacherId, string $dayName,
                                        string $timeStart, string $timeEnd): void
{
    $ok = DB::table('teacher_availabilities')
        ->where('user_id', $teacherId)
        ->where('day', $dayName)
        ->where('start_time', '<=', $timeStart)
        ->where('end_time', '>=', $timeEnd)
        ->exists();

    if (! $ok) {
        throw ValidationException::withMessages([
            'teacher_id' => "Selected teacher is not available on {$dayName} {$timeStart}–{$timeEnd}.",
        ]);
    }
}

/** Room daily window containment + status. */
private function assertRoomAvailable(int $roomId, string $timeStart, string $timeEnd): void
{
    $ok = DB::table('rooms')
        ->where('id', $roomId)
        ->where('status', 'available')
        ->where('daily_start_time', '<=', $timeStart)
        ->where('daily_end_time', '>=', $timeEnd)
        ->exists();

    if (! $ok) {
        throw ValidationException::withMessages([
            'room_id' => 'Selected room is not available for this time window.',
        ]);
    }
}

/**
 * No teacher conflicts in day/time/date window (ignoring one meeting if provided).
 * Uses cm/co + applyOverlap() for time/date.
 */
private function assertNoTeacherConflicts(int $teacherId, int $day,
                                          string $timeStart, string $timeEnd,
                                          string $dateStart, string $dateEnd,
                                          ?int $ignoreMeetingId = null): void
{
    $exists = DB::table('class_meetings as cm')
        ->join('class_offerings as co', 'co.id', '=', 'cm.class_offering_id')
        ->where('co.status', 'active')
        ->where('cm.teacher_id', $teacherId)
        ->when($ignoreMeetingId, fn ($q) => $q->where('cm.id', '!=', $ignoreMeetingId))
        ->tap(fn ($q) => $this->applyOverlap($q, $day, $timeStart, $timeEnd, $dateStart, $dateEnd))
        ->exists();

    if ($exists) {
        throw ValidationException::withMessages([
            'teacher_id' => 'Teacher has a conflicting class in that window.',
        ]);
    }
}

/** No room conflicts in day/time/date window (ignoring one meeting if provided). */
private function assertNoRoomConflicts(int $roomId, int $day,
                                       string $timeStart, string $timeEnd,
                                       string $dateStart, string $dateEnd,
                                       ?int $ignoreMeetingId = null): void
{
    $exists = DB::table('class_meetings as cm')
        ->join('class_offerings as co', 'co.id', '=', 'cm.class_offering_id')
        ->where('co.status', 'active')
        ->where('cm.room_id', $roomId)
        ->when($ignoreMeetingId, fn ($q) => $q->where('cm.id', '!=', $ignoreMeetingId))
        ->tap(fn ($q) => $this->applyOverlap($q, $day, $timeStart, $timeEnd, $dateStart, $dateEnd))
        ->exists();

    if ($exists) {
        throw ValidationException::withMessages([
            'room_id' => 'Room is occupied in that window.',
        ]);
    }
}

private function syncOfficialCurriculumEnrollmentForSection(
    int $sectionId,
    int $curriculumTermSubjectId,
    ?int $classOfferingId = null,
    bool $enroll = true
): void {
    $academicIds = StudentAcademic::where('section_id', $sectionId)->pluck('id');

    if ($academicIds->isEmpty()) return;

    $query = StudentCurriculumSubject::whereIn('student_academic_id', $academicIds)
        ->where('curriculum_term_subject_id', $curriculumTermSubjectId);

    if ($enroll) {
        // don't overwrite passed/credited
        $query->whereIn('status', ['not_taken', 'failed'])
              ->update([
                  'status' => 'enrolled',
                  'class_offering_id' => $classOfferingId,
              ]);
    } else {
        // revert only those enrolled under this offering (safer)
        $query->where('status', 'enrolled')
              ->when($classOfferingId, fn($q) => $q->where('class_offering_id', $classOfferingId))
              ->update([
                  'status' => 'not_taken',
                  'class_offering_id' => null,
              ]);
    }
}



/** No section conflicts in day/time/date window (ignoring one meeting if provided). */
private function assertNoSectionConflicts(int $sectionId, int $day,
                                          string $timeStart, string $timeEnd,
                                          string $dateStart, string $dateEnd,
                                          ?int $ignoreMeetingId = null): void
{
    $exists = DB::table('class_meetings as cm')
        ->join('class_offerings as co', 'co.id', '=', 'cm.class_offering_id')
        ->where('co.status', 'active')
        ->where('co.section_id', $sectionId)
        ->when($ignoreMeetingId, fn ($q) => $q->where('cm.id', '!=', $ignoreMeetingId))
        ->tap(fn ($q) => $this->applyOverlap($q, $day, $timeStart, $timeEnd, $dateStart, $dateEnd))
        ->exists();

    if ($exists) {
        throw ValidationException::withMessages([
            'day_of_week' => 'This section already has a class in that window.',
        ]);
    }
}

public function offeringsIndex(Request $request)
{
    $user = Auth::user();
    $programId = $user->program_id; // super admin may be null

    $search = trim((string) $request->get('q'));

    $q = DB::table('class_offerings as co')
        ->join('sections as s', 's.id', '=', 'co.section_id')
        ->join('programs as p', 'p.id', '=', 's.program_id')
        ->join('curriculum_term_subjects as cts', 'cts.id', '=', 'co.curriculum_term_subject_id')
        ->join('subjects as sub', 'sub.id', '=', 'cts.subject_id')
        ->leftJoin('class_offering_finalizations as cof', 'cof.class_offering_id', '=', 'co.id')
        ->select([
            'co.id as offering_id',
            'co.section_id',
            'co.start_date',
            'co.end_date',
            'co.status as offering_status',

            's.name as section_name',
            's.program_id',
            'p.program_name',

            'sub.code as subject_code',
            'sub.name as subject_name',

            'cof.finalized_at',
            'cof.finalized_by',
            'cof.unlocked_at',
            'cof.unlocked_by',
            'cof.unlock_reason',
        ])
        ->orderByDesc('co.end_date')
        ->orderByDesc('co.start_date')
        ->orderByDesc('co.id');

    // ✅ Program guard (program admin only sees their program offerings)
    if ($programId) {
        $q->where('s.program_id', $programId);
    }

    // ✅ Search (by section / subject / program)
    if ($search !== '') {
        $q->where(function ($w) use ($search) {
            $w->where('s.name', 'like', "%{$search}%")
              ->orWhere('sub.code', 'like', "%{$search}%")
              ->orWhere('sub.name', 'like', "%{$search}%")
              ->orWhere('p.program_name', 'like', "%{$search}%");
        });
    }

    $offerings = $q->paginate(20)->withQueryString();

    return view('admin.schedules.offerings.index', compact('offerings', 'search'));
}

public function offeringStatus(int $offeringId)
{
    $user = Auth::user();
    $programId = $user->program_id;

    $offering = ClassOffering::query()
        ->with([
            'section.program',
            'curriculumTermSubject.subject',
        ])
        ->findOrFail($offeringId);

    // ✅ Program guard
    if ($programId && (int)$offering->section?->program_id !== (int)$programId) {
        abort(403);
    }

    $finalization = ClassOfferingFinalization::query()
        ->with(['finalizedBy', 'unlockedBy'])
        ->where('class_offering_id', $offering->id)
        ->first();

    $isFinalized = $finalization && $finalization->finalized_at && is_null($finalization->unlocked_at);

    return view('admin.schedules.offerings.status', compact('offering', 'finalization', 'isFinalized'));
}





private function getTeacherMaxUnits(int $teacherId): float
{
    $max = DB::table('teacher_load_settings')
        ->where('user_id', $teacherId)
        ->value('max_units');

    return (float) ($max ?? 0);
}

/**
 * Sum units of ACTIVE offerings assigned to this teacher.
 * Counts each offering once (not per meeting).
 */
private function getTeacherActiveUnits(int $teacherId, ?int $excludeOfferingId = null): float
{
    // We must count EACH offering once, even if it has multiple meetings.
    // DO NOT use SUM(DISTINCT cts.unit) because it undercounts when multiple offerings share the same unit value.

    $distinctOfferings = DB::table('class_meetings as cm')
        ->join('class_offerings as co', 'co.id', '=', 'cm.class_offering_id')
        ->join('curriculum_term_subjects as cts', 'cts.id', '=', 'co.curriculum_term_subject_id')
        ->where('cm.teacher_id', $teacherId)
        ->where('co.status', 'active')
        ->whereDate('co.end_date', '>=', now()->toDateString())
        ->when($excludeOfferingId, fn($q) => $q->where('co.id', '!=', $excludeOfferingId))
        ->selectRaw('DISTINCT co.id as offering_id, cts.unit as unit');

    $sum = DB::query()
        ->fromSub($distinctOfferings, 'x')
        ->selectRaw('COALESCE(SUM(unit),0) as total_units')
        ->value('total_units');

    return (float) ($sum ?? 0);
}

private function assertTeacherWithinMaxLoad(int $teacherId, float $newUnits, ?int $excludeOfferingId = null): void
{
    $max = $this->getTeacherMaxUnits($teacherId);
    $current = $this->getTeacherActiveUnits($teacherId, $excludeOfferingId);

    if ($max <= 0) {
        throw ValidationException::withMessages([
            'teacher_id' => 'This teacher has no Max Load set yet.',
        ]);
    }

    if (($current + $newUnits) > $max) {
        throw ValidationException::withMessages([
            'teacher_id' => "Teacher exceeds max load. Current load: {$current} unit(s), New subject: {$newUnits} unit(s), Max: {$max} unit(s).",
        ]);
    }
}

public function downloadScheduleReportPdf(Request $request)
{
    $user = Auth::user();
    $programId = $user->program_id; // nullable for super admin
    $today = now()->toDateString();

    // Your system stores schedules using class_offerings + class_meetings.
    // This query collects every active meeting row with its teacher, section, subject, and room.
    $scheduleRows = DB::table('class_meetings as cm')
        ->join('class_offerings as co', 'co.id', '=', 'cm.class_offering_id')
        ->join('sections as sec', 'sec.id', '=', 'co.section_id')
        ->join('programs as p', 'p.id', '=', 'sec.program_id')
        ->join('curriculum_term_subjects as cts', 'cts.id', '=', 'co.curriculum_term_subject_id')
        ->join('subjects as subj', 'subj.id', '=', 'cts.subject_id')
        ->join('users as teacher', 'teacher.id', '=', 'cm.teacher_id')
        ->join('rooms as room', 'room.id', '=', 'cm.room_id')
        ->leftJoin('curricula as cur', 'cur.id', '=', 'sec.curriculum_id')
        ->where('co.status', 'active')
        ->where('sec.status', 'active')
        ->whereDate('co.end_date', '>=', $today)
        ->whereNull('cm.deleted_at')
        ->when($programId, function ($query) use ($programId) {
            $query->where('sec.program_id', $programId);
        })
        ->select([
            'co.id as class_offering_id',
            'co.start_date',
            'co.end_date',

            'cm.id as class_meeting_id',
            'cm.day_of_week',
            'cm.time_start',
            'cm.time_end',

            'teacher.id as teacher_id',
            'teacher.first_name as teacher_first_name',
            'teacher.last_name as teacher_last_name',
            'teacher.school_id as teacher_school_id',

            'sec.id as section_id',
            'sec.name as section_name',
            'sec.year_level',
            'sec.term_no',

            'p.program_name',
            'cur.code as curriculum_code',

            'subj.code as subject_code',
            'subj.name as subject_name',
            'cts.unit as units',
            'cts.type as subject_type',

            'room.name as room_name',
        ])
        ->orderBy('teacher.last_name')
        ->orderBy('teacher.first_name')
        ->orderBy('cm.day_of_week')
        ->orderBy('cm.time_start')
        ->get();

    // Part 1: same rows, grouped by faculty.
    $facultySchedules = $scheduleRows
        ->groupBy('teacher_id')
        ->map(function ($rows) {
            $first = $rows->first();

            $totalUnits = $rows
                ->unique('class_offering_id')
                ->sum(function ($row) {
                    return (float) $row->units;
                });

            return (object) [
                'teacher_id' => $first->teacher_id,
                'teacher_name' => trim($first->teacher_first_name . ' ' . $first->teacher_last_name),
                'teacher_school_id' => $first->teacher_school_id,
                'total_units' => $totalUnits,
                'rows' => $rows->sortBy([
                    ['day_of_week', 'asc'],
                    ['time_start', 'asc'],
                ])->values(),
            ];
        })
        ->sortBy('teacher_name')
        ->values();

    // Load all active sections so a section can still appear even if it has no schedule yet.
    $sections = DB::table('sections as sec')
        ->join('programs as p', 'p.id', '=', 'sec.program_id')
        ->leftJoin('curricula as cur', 'cur.id', '=', 'sec.curriculum_id')
        ->where('sec.status', 'active')
        ->when($programId, function ($query) use ($programId) {
            $query->where('sec.program_id', $programId);
        })
        ->select([
            'sec.id',
            'sec.name as section_name',
            'sec.year_level',
            'sec.term_no',
            'p.program_name',
            'cur.code as curriculum_code',
        ])
        ->orderBy('p.program_name')
        ->orderBy('sec.year_level')
        ->orderBy('sec.term_no')
        ->orderBy('sec.name')
        ->get();

    // Part 2: same rows, grouped by section.
    $sectionSchedules = $sections->map(function ($section) use ($scheduleRows) {
        $rows = $scheduleRows
            ->where('section_id', $section->id)
            ->sortBy([
                ['day_of_week', 'asc'],
                ['time_start', 'asc'],
            ])
            ->values();

        return (object) [
            'section' => $section,
            'rows' => $rows,
        ];
    });

    $days = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday',
    ];

    $pdf = Pdf::loadView('admin.schedules.sections.report-pdf', [
        'facultySchedules' => $facultySchedules,
        'sectionSchedules' => $sectionSchedules,
        'days' => $days,
        'schoolName' => 'Granby Colleges Of Science and Technology',
        'schoolAddress' => 'Ibayo Silangan, Naic, Cavite, Philippines',
        'schoolContact' => 'Tel: (63) 111-2222 - Email: Granby@gmail.com',
        'dateIssued' => now()->format('F d, Y'),
        'preparedBy' => trim($user->first_name . ' ' . $user->last_name),
    ])->setPaper('a4', 'portrait');

    $filename = 'schedule_report_' . now()->format('Y-m-d') . '.pdf';

    return $pdf->stream($filename);
}


}
