<?php
// app/Http/Controllers/SectionController.php
namespace App\Http\Controllers;


use App\Http\Requests\StoreSectionRequest;
use App\Http\Requests\UpdateSectionRequest;
use App\Models\{Program, CurriculumTerm, Section};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\StudentAcademic;

class SectionController extends Controller
{
  // List sections in the user's program
// app/Http/Controllers/SectionController.php

// View + batch-edit students in a section
public function students(Request $request, Section $section)
{
    $this->authorizeSameProgram($section);

    // Load section + program/curriculum info (with duration and terms_per_year)
    $sectionInfo = DB::table('sections as s')
        ->join('programs as p', 'p.id', '=', 's.program_id')
        ->join('curricula as c', 'c.id', '=', 's.curriculum_id')
        ->where('s.id', $section->id)
        ->select([
            's.*',
            'p.program_name',
            'p.duration as program_duration',
            'p.terms_per_year',
            'c.code as curriculum_code',
        ])
        ->first();

    abort_if(!$sectionInfo, 404);

    // Students in this section (role_id = 4 = student)
    $students = DB::table('student_academics as sa')
        ->join('users as u', 'u.id', '=', 'sa.user_id')
        ->join('user_roles as ur', function ($join) {
            $join->on('ur.user_id', '=', 'u.id')
                 ->where('ur.role_id', 4);
        })
        ->where('sa.section_id', $section->id)
        ->select([
            'sa.id as academic_id',
            'u.id as user_id',
            'u.first_name',
            'u.last_name',
            'u.email',
            'u.school_id',
            'u.gender',
            'sa.status as academic_status',
            'sa.enrollment_status',
        ])
        ->orderBy('u.last_name')
        ->orderBy('u.first_name')
        ->get();

    // Candidate sections: same program, curriculum, year, term, active, not this one
    $candidateSections = DB::table('sections')
        ->where('program_id', $section->program_id)
        ->where('curriculum_id', $section->curriculum_id)
        ->where('year_level', $section->year_level)
        ->where('term_no', $section->term_no)
        ->where('status', 'active')
        ->where('id', '!=', $section->id)
        ->orderBy('name')
        ->get();

    // Enum options from student_academics.enrollment_status
    $enrollmentStatuses = ['enrolled', 'dropped', 'graduated'];

    // Reuse the same Blade you already use for schedules
    return view('admin.schedules.sections.students', [
        'section'            => $sectionInfo,
        'students'           => $students,
        'candidateSections'  => $candidateSections,
        'enrollmentStatuses' => $enrollmentStatuses,
    ]);
}




public function index(Request $req)
{
    // Read filters from query string
    $status  = strtolower((string) $req->query('status', 'active'));
    $q       = trim((string) $req->query('q', ''));
    $perPage = (int) $req->query('per_page', 15);

    if (! in_array($status, ['active', 'archived', 'all'], true)) {
        $status = 'active';
    }

    if (! in_array($perPage, [10, 15, 25, 50, 100], true)) {
        $perPage = 15;
    }

    // Base query = only sections in the user's program
    $base = Section::query()
        ->where('program_id', $req->user()->program_id)
        ->when($q !== '', function ($query) use ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('year_level', 'like', "%{$q}%")
                    ->orWhere('term_no', 'like', "%{$q}%")
                    ->orWhere('capacity', 'like', "%{$q}%");
            });
        });

    // Apply status filter
    $sections = match ($status) {
        'archived' => (clone $base)->where('status', 'archived'),
        'all'      => (clone $base),
        default    => (clone $base)->where('status', 'active'),
    };

    $sections = $sections
        ->orderBy('year_level')
        ->orderBy('term_no')
        ->orderBy('name')
        ->paginate($perPage)
        ->appends($req->only('status', 'q', 'per_page'));

    // Counts for tabs
    $programId = $req->user()->program_id;

    $counts = [
        'active'   => Section::where('program_id', $programId)->where('status', 'active')->count(),
        'archived' => Section::where('program_id', $programId)->where('status', 'archived')->count(),
        'all'      => Section::where('program_id', $programId)->count(),
    ];

    return view('sections.index', compact('sections', 'counts', 'status', 'q', 'perPage'));
}


  // Show create form
  public function create(Request $req){
    $program = Program::select('id','curriculum_id')->find($req->user()->program_id);

    // Load all valid year/term pairs for dropdowns (ordered by sequence if you have it)
    $terms = CurriculumTerm::where('curriculum_id', $program->curriculum_id)
      ->orderBy('year_level')->orderBy('term_no')->get();

    $default = $terms->first(); // earliest term (min y, then min t)
    return view('sections.create', compact('terms','default'));
  }

  // Create
  public function store(StoreSectionRequest $req){
    $user    = $req->user();
    $program = Program::select('id','curriculum_id')->find($user->program_id);

    // Default to earliest term in curriculum
    $earliest = CurriculumTerm::where('curriculum_id', $program->curriculum_id)
      ->orderBy('year_level')->orderBy('term_no')->first();

    if (!$earliest) {
      return back()->withErrors(['curriculum' => 'The present curriculum has no defined terms.'])
                   ->withInput();
    }

    $y = $req->input('year_level', $earliest->year_level);
    $t = $req->input('term_no',    $earliest->term_no);

    try {
      DB::transaction(function() use ($req, $user, $program, $y, $t) {
        Section::create([
          'program_id'    => $program->id,
          'curriculum_id' => $program->curriculum_id,
          'year_level'    => $y,
          'term_no'       => $t,
          'name'          => (string)$req->string('name'),
          'capacity'      => (int)$req->integer('capacity'),
          'status'        => 'active',
          'created_by'    => $user->id,
        ]);
      });
    } catch (\Illuminate\Database\QueryException $e) {
      // likely unique(program_id, name) collision
      return back()->withErrors(['name' => 'This name already exists for this program.'])->withInput();
    }

    return redirect()->route('sections.index')->with('status', 'Section created.');
  }

  // Show edit form
  public function edit(Section $section){
    $this->authorizeSameProgram($section);
    return view('sections.edit', compact('section'));
  }

  // Update (allow name, capacity, status, year/term)  // Update (allow name, capacity, status, year/term)
  public function update(UpdateSectionRequest $req, Section $section)
  {
      $this->authorizeSameProgram($section);

      // New values coming from the form
      $data = $req->only(['name', 'capacity', 'status', 'year_level', 'term_no']);

      $newStatus    = $data['status']     ?? $section->status;
      $newYearLevel = (int) ($data['year_level'] ?? $section->year_level);
      $newTermNo    = (int) ($data['term_no']    ?? $section->term_no);

      // 🔁 5.1 Same archive rule as the archive() action
      if ($newStatus === 'archived' && $section->status !== 'archived') {
          if ($this->sectionHasNonGraduatedStudents($section)) {
              return back()
                  ->withErrors([
                      'status' => 'You can only archive a section if all students in it are already '
                                . 'marked as graduated, or if the section has no students.',
                  ])
                  ->withInput();
          }
      }

      // 🔁 5.2 Same "no active offerings in past term" rule
      // when the user manually moves the section forward to a later term.
      $isMovingForward =
          ($newYearLevel > $section->year_level) ||
          ($newYearLevel === (int)$section->year_level && $newTermNo > (int)$section->term_no);

      if ($isMovingForward) {
          // We check using the OLD (current) term of the section –
          // that term will become the “past term” after saving.
          if ($this->sectionHasActiveOfferingsInCurrentTerm($section)) {
              return back()
                  ->withErrors([
                      'year_level' => 'This section still has active class offerings in its current term. '
                                    . 'Please archive/close those offerings before moving the section to '
                                    . 'a higher year/term.',
                  ])
                  ->withInput();
          }
      }

      // If all validations passed, apply the changes
      $section->update($data);

      return redirect()
          ->route('sections.index')
          ->with('status', 'Section updated.');
  }


  // Promote to the next valid (year, term)  // Promote to the next valid (year, term)
  public function promote(Section $section)
  {
      $this->authorizeSameProgram($section);

      // 🔒 RULE 1: Do NOT promote if the section still has
      // active offerings in its current (soon-to-be "past") term.
      if ($this->sectionHasActiveOfferingsInCurrentTerm($section)) {
          return back()
              ->withErrors([
                  'promotion' => 'This section still has active class offerings in its current term. '
                               . 'Please archive/close those offerings before promoting the section.',
              ]);
      }

      // Next = the first term after current (ordered by year, then term)
      $next = CurriculumTerm::where('curriculum_id', $section->curriculum_id)
          ->where(function ($q) use ($section) {
              $q->where('year_level', '>', $section->year_level)
                ->orWhere(function ($q2) use ($section) {
                    $q2->where('year_level', $section->year_level)
                       ->where('term_no', '>', $section->term_no);
                });
          })
          ->orderBy('year_level')
          ->orderBy('term_no')
          ->first();

      if (!$next) {
          return back()->withErrors([
              'term_no' => 'End of curriculum. No next term/year.',
          ]);
      }

      $section->update([
          'year_level' => $next->year_level,
          'term_no'    => $next->term_no,
      ]);

      return back()->with('status', 'Section promoted.');
  }


   public function archive(Section $section)
  {
      $this->authorizeSameProgram($section);

      // 🔒 RULE 2: Archive only if:
      //   - there are NO students in this section, OR
      //   - all students in this section are enrolled_status = 'graduated'
      if ($this->sectionHasNonGraduatedStudents($section)) {
          return back()
              ->withErrors([
                  'status' => 'You can only archive a section if all students in it are already '
                            . 'marked as graduated, or if the section has no students.',
              ]);
      }

      $section->update(['status' => 'archived']);

      return back()->with('status', 'Section archived.');
  }


  public function restore(Section $section){
    $this->authorizeSameProgram($section);
    $section->update(['status' => 'active']);
    return back()->with('status', 'Section restored.');
  }


    /**
   * Check if this section still has any ACTIVE class offerings
   * in its CURRENT curriculum term (year_level + term_no).
   *
   * This is used to block promotion (or moving year/term forward)
   * while the “past term” still has active offerings.
   */
 private function sectionHasActiveOfferingsInCurrentTerm(Section $section): bool
{
    $today = now()->toDateString();

    return DB::table('class_offerings as co')
        ->join('curriculum_term_subjects as cts', 'cts.id', '=', 'co.curriculum_term_subject_id')
        ->join('curriculum_terms as ct', 'ct.id', '=', 'cts.curriculum_term_id')
        ->where('co.section_id', $section->id)

        // still needs to be marked active
        ->where('co.status', 'active')

        // ✅ IMPORTANT: only treat as “active” if the offering hasn't ended yet
        // (so offerings from a finished term do NOT block updates/promotions)
        ->where(function ($q) use ($today) {
            $q->whereNull('co.end_date')
              ->orWhereDate('co.end_date', '>=', $today);
        })

        ->where('ct.curriculum_id', $section->curriculum_id)
        ->where('ct.year_level', $section->year_level)
        ->where('ct.term_no', $section->term_no)
        ->exists();
}


  /**
   * Check if the section has any NON-graduated students.
   * - If there are no rows in student_academics for this section → OK
   * - If all rows are enrollment_status = 'graduated' → OK
   * - Anything else blocks archiving.
   */
  private function sectionHasNonGraduatedStudents(Section $section): bool
  {
      return StudentAcademic::where('section_id', $section->id)
          ->where('enrollment_status', '!=', 'graduated')
          ->exists();
  }


  private function authorizeSameProgram(Section $section): void {
    abort_unless(auth()->check() && $section->program_id === auth()->user()->program_id, 403);
  }
}
