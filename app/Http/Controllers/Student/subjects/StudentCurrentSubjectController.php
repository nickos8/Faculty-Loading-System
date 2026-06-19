<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassOffering;
use App\Models\StudentAcademic;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;

class StudentCurrentSubjectController extends Controller
{
    public function index(Request $request)
    {
        $student = $this->getAuthenticatedStudent();

        $sa      = $this->getStudentAcademic($student);
        $section = $this->buildSectionObject($sa);
        $today   = now()->toDateString();

        $allOfferings      = $this->resolveAllOfferings($student, $sa, $today);
        $enrolledSubjects  = $this->extractEnrolledSubjects($allOfferings);

        $totalUnits = $enrolledSubjects->sum(fn ($cts) => (float) ($cts->unit ?? 0));
        $byType     = $enrolledSubjects
            ->groupBy(fn ($cts) => $cts->type ?? 'other')
            ->map->count();

        return view('student.subjects.index', compact(
            'section',
            'enrolledSubjects',
            'totalUnits',
            'byType',
            'sa',
        ));
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function getAuthenticatedStudent(): User
    {
        $student = Auth::user();

        if (! $student) {
            abort(403, 'Not authenticated as student.');
        }

        return $student;
    }

    private function getStudentAcademic(User $student): ?StudentAcademic
    {
        return $student
            ->studentAcademic()
            ->with(['program', 'section', 'curriculum'])
            ->first();
    }

    private function buildSectionObject(?StudentAcademic $sa): object
    {
        return (object) [
            'id'           => $sa?->section?->id,
            'name'         => $sa?->section?->name ?? 'No section assigned',
            'program_name' => $sa?->program?->program_name ?? '—',
        ];
    }

    /**
     * Returns offerings from the student's section (if any).
     *
     * @return Collection<int, ClassOffering>
     */
    private function getSectionOfferings(?StudentAcademic $sa, string $today): Collection
    {
        if (! $sa || ! $sa->section_id) {
            return new Collection();
        }

        return ClassOffering::query()
            ->with(['curriculumTermSubject.subject'])
            ->where('section_id', $sa->section_id)
            ->where('status', 'active')
            ->whereDate('end_date', '>=', $today)
            ->get();
    }

    /**
     * Returns offerings the student is explicitly enrolled in via the pivot table.
     *
     * @return Collection<int, ClassOffering>
     */
    private function getEnrollmentOfferings(User $student, string $today): Collection
    {
        return ClassOffering::query()
            ->with(['curriculumTermSubject.subject'])
            ->where('status', 'active')
            ->whereDate('end_date', '>=', $today)
            ->whereHas('studentEnrollments', function ($q) use ($student): void {
                $q->where('user_id', $student->id)
                  ->where('status', 'enrolled');
            })
            ->get();
    }

    /**
     * Merges section and enrollment offerings, removing duplicates.
     *
     * @return SupportCollection<int, ClassOffering>
     */
    private function resolveAllOfferings(User $student, ?StudentAcademic $sa, string $today): SupportCollection
    {
        $sectionOfferings    = $this->getSectionOfferings($sa, $today);
        $enrollmentOfferings = $this->getEnrollmentOfferings($student, $today);

        return $sectionOfferings
            ->concat($enrollmentOfferings)
            ->unique('id')
            ->values();
    }

    /**
     * Extracts unique CurriculumTermSubject records from offerings, sorted by subject code.
     *
     * @param  SupportCollection<int, ClassOffering> $allOfferings
     * @return SupportCollection<int, \App\Models\CurriculumTermSubject>
     */
    private function extractEnrolledSubjects(SupportCollection $allOfferings): SupportCollection
    {
        return $allOfferings
            ->map(fn (ClassOffering $offering) => $offering->curriculumTermSubject)
            ->filter()
            ->unique('id')
            ->sortBy(fn ($cts) => $cts->subject?->code)
            ->values();
    }
}
