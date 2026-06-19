<?php
// app/Http/Controllers/CurriculumTermSubjectController.php

namespace App\Http\Controllers;

use App\Models\{CurriculumTerm, CurriculumTermSubject, Subject};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class CurriculumTermSubjectController extends Controller
{
    private function termLabel(CurriculumTerm $term): string
    {
        return "Year {$term->year_level}, Term {$term->term_no}";
    }

    private function normalizeType(?string $type): ?string
    {
        return $type ? strtoupper($type) : null;
    }


    public function edit(CurriculumTerm $term, CurriculumTermSubject $cts)
{
    // Safety: prevent editing a CTS that doesn't belong to this term
    abort_unless((int)$cts->curriculum_term_id === (int)$term->id, 404);

    $cts->load(['subject', 'term.curriculum.program']);

    // Subject choices for dropdown (include prerequisites for better UX if needed later)
    $choices = Subject::query()
        ->select('id', 'code', 'name')
        ->orderBy('code')
        ->get();

    return view('curricula.term_subject_edit', [
        'term' => $term,
        'cts' => $cts,
        'choices' => $choices,
    ]);
}

public function update(Request $req, CurriculumTerm $term, CurriculumTermSubject $cts)
{
    abort_unless((int)$cts->curriculum_term_id === (int)$term->id, 404);

    $validator = Validator::make($req->all(), [
        'subject_id' => ['required', 'exists:subjects,id'],
        'unit'       => ['required', 'numeric', 'min:0.5', 'max:10'],
        'type'       => ['required', 'in:major,minor,elective,general,thesis,internship'],
    ]);

    if ($validator->fails()) {
        return back()->withErrors($validator)->withInput();
    }

    $data = $validator->validated();

    $term->load('curriculum');

    $oldSubjectId = (int) $cts->subject_id;
    $newSubjectId = (int) $data['subject_id'];

    // If there are class offerings, DO NOT allow changing the subject
    // (units/type is usually safe; subject swap will "rename" existing schedules)
    if ($newSubjectId !== $oldSubjectId && $cts->classOfferings()->exists()) {
        return back()
            ->withErrors([
                'subject_id' => 'You cannot change the subject because this curriculum subject already has class offerings. Update/cancel those offerings first.',
            ])
            ->withInput();
    }

    // Helpful for error messages
    $termLabel = $this->termLabel($term);

    $selected = DB::table('subjects')
        ->select('id', 'code', 'name')
        ->where('id', $newSubjectId)
        ->first();

    // If subject changed, re-run the SAME business rules you do in store()
    if ($newSubjectId !== $oldSubjectId) {

        // 1) Duplicate check inside this curriculum (exclude current CTS row)
        $existingPlacement = DB::table('curriculum_term_subjects as cts')
            ->join('curriculum_terms as ct', 'ct.id', '=', 'cts.curriculum_term_id')
            ->where('ct.curriculum_id', $term->curriculum_id)
            ->where('cts.subject_id', $newSubjectId)
            ->where('cts.id', '!=', $cts->id)
            ->select('ct.year_level', 'ct.term_no', 'ct.sequence')
            ->first();

        if ($existingPlacement) {
            $msg =
                "Unable to update subject in {$termLabel}.\n\n" .
                "Selected Subject: {$selected->code} — {$selected->name}\n" .
                "Reason: This subject is already included in this curriculum.\n" .
                "Existing Placement: Year {$existingPlacement->year_level}, Term {$existingPlacement->term_no}\n\n" .
                "Action Required: Remove the existing placement first, or choose a different subject.";

            return back()
                ->withErrors(['subject_id' => $msg])
                ->withInput();
        }

        // 2) Prerequisite check — must be placed in earlier terms
        $targetSeq = (int) $term->sequence;

        $prereqIds = DB::table('subject_prerequisites')
            ->where('subject_id', $newSubjectId)
            ->pluck('prerequisite_subject_id')
            ->values();

        if ($prereqIds->isNotEmpty()) {
            $placedPrereqs = DB::table('curriculum_term_subjects as cts')
                ->join('curriculum_terms as ct', 'ct.id', '=', 'cts.curriculum_term_id')
                ->where('ct.curriculum_id', $term->curriculum_id)
                ->whereIn('cts.subject_id', $prereqIds)
                ->where('ct.sequence', '<', $targetSeq)
                ->select('cts.subject_id', 'ct.year_level', 'ct.term_no')
                ->get()
                ->keyBy('subject_id');

            $missingIds = $prereqIds->reject(fn ($id) => $placedPrereqs->has($id))->values();

            if ($missingIds->isNotEmpty()) {
                $missing = DB::table('subjects')
                    ->whereIn('id', $missingIds)
                    ->select('id', 'code', 'name')
                    ->orderBy('code')
                    ->get();

                $missingLines = $missing->map(fn ($m) => "- {$m->code} — {$m->name}")->implode("\n");

                $msg =
                    "Unable to update subject in {$termLabel}.\n\n" .
                    "Selected Subject: {$selected->code} — {$selected->name}\n" .
                    "Reason: Prerequisite requirement not satisfied.\n\n" .
                    "Missing Prerequisite(s) — must be placed in an earlier term:\n{$missingLines}\n\n" .
                    "Action Required:\n" .
                    "1) Add the missing prerequisite subjects to earlier terms.\n" .
                    "2) Then return here and update again.";

                return back()
                    ->withErrors(['subject_id' => $msg])
                    ->withInput();
            }
        }
    }

    // Update is safe
    $cts->update([
        'subject_id' => $newSubjectId,
        'unit'       => $data['unit'],
        'type'       => $data['type'],
    ]);

    // Redirect back to curriculum show and highlight the term
    return redirect()
        ->route('curricula.show', $term->curriculum_id)
        ->with('flash_term_id', $term->id)
        ->with('success_editSubject', [
            'term_id' => $term->id,
            'cts_id'  => $cts->id,
            'subject' => [
                'code' => $selected->code ?? null,
                'name' => $selected->name ?? null,
            ],
            'unit' => (float) $data['unit'],
            'type' => $this->normalizeType($data['type'] ?? null),
            'message' => 'Subject updated successfully.',
        ]);
}




    public function updateDates(Request $request, CurriculumTerm $term)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator, 'termDates')
                ->withInput()
                ->with('flash_term_id', $term->id)
                ->with('open_dates_editor', $term->id);
        }

        $data = $validator->validated();

        DB::transaction(function () use ($term, $data) {
            // Update the term (single source of truth)
            $term->update($data);

            // Sync all offerings under this term (Phase 1 compatibility)
            DB::table('class_offerings as co')
                ->join('curriculum_term_subjects as cts', 'cts.id', '=', 'co.curriculum_term_subject_id')
                ->where('cts.curriculum_term_id', $term->id)
                ->update([
                    'co.start_date' => $data['start_date'],
                    'co.end_date'   => $data['end_date'],
                    'co.updated_at' => now(),
                ]);
        });

        return back()
            ->with('flash_term_id', $term->id)
            ->with('open_dates_editor', $term->id) // keep panel open so user sees the success
            ->with('success_termDates', [
                'title' => 'Term dates updated',
                'term'  => [
                    'id' => $term->id,
                    'label' => $this->termLabel($term),
                ],
                'start_date' => $data['start_date'],
                'end_date'   => $data['end_date'],
                'message'    => 'Term dates updated and synced to offerings.',
            ]);
    }

    public function store(Request $req, CurriculumTerm $term)
    {
        $validator = Validator::make($req->all(), [
            'subject_id' => ['required', 'exists:subjects,id'],
            'unit'       => ['required', 'numeric', 'min:0.5', 'max:10'],
            'type'       => ['required', 'in:major,minor,elective,general,thesis,internship'],
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator, 'addSubject')
                ->withInput()
                ->with('flash_term_id', $term->id)
                ->with('open_term_modal', $term->id);
        }

        $data = $validator->validated();

        $curriculum = $term->curriculum;
        $targetSeq  = $term->sequence;
        $termLabel  = $this->termLabel($term);

        // Helpful context for messages (code/name)
        $selected = DB::table('subjects')
            ->select('id', 'code', 'name')
            ->where('id', $data['subject_id'])
            ->first();

        // 1) DUPLICATE CHECK — subject already exists somewhere in this curriculum
        $existingPlacement = DB::table('curriculum_term_subjects as cts')
            ->join('curriculum_terms as ct', 'ct.id', '=', 'cts.curriculum_term_id')
            ->where('ct.curriculum_id', $curriculum->id)
            ->where('cts.subject_id', $data['subject_id'])
            ->select('ct.year_level', 'ct.term_no', 'ct.sequence')
            ->first();

        if ($existingPlacement) {
            $msg =
                "Unable to add subject to {$termLabel}.\n\n" .
                "Selected Subject: {$selected->code} — {$selected->name}\n" .
                "Reason: This subject is already included in this curriculum.\n" .
                "Existing Placement: Year {$existingPlacement->year_level}, Term {$existingPlacement->term_no}\n\n" .
                "Action Required: Remove the existing placement first, or choose a different subject.";

            return back()
                ->withErrors(['subject_id' => $msg], 'addSubject')
                ->withInput()
                ->with('flash_term_id', $term->id)
                ->with('open_term_modal', $term->id);
        }

        // 2) PREREQUISITE CHECK — must be placed in earlier terms
        $prereqIds = DB::table('subject_prerequisites')
            ->where('subject_id', $data['subject_id'])
            ->pluck('prerequisite_subject_id')
            ->values();

        if ($prereqIds->isNotEmpty()) {
            // prereqs already placed earlier
            $placedPrereqs = DB::table('curriculum_term_subjects as cts')
                ->join('curriculum_terms as ct', 'ct.id', '=', 'cts.curriculum_term_id')
                ->where('ct.curriculum_id', $curriculum->id)
                ->whereIn('cts.subject_id', $prereqIds)
                ->where('ct.sequence', '<', $targetSeq)
                ->select('cts.subject_id', 'ct.year_level', 'ct.term_no')
                ->get()
                ->keyBy('subject_id');

            $missingIds = $prereqIds->reject(fn ($id) => $placedPrereqs->has($id))->values();

            if ($missingIds->isNotEmpty()) {
                $missing = DB::table('subjects')
                    ->whereIn('id', $missingIds)
                    ->select('id', 'code', 'name')
                    ->orderBy('code')
                    ->get();

                $missingLines = $missing->map(fn ($m) => "- {$m->code} — {$m->name}")->implode("\n");

                // Optional: show prereqs that ARE satisfied and where they are placed
                $satisfiedLines = '';
                if ($placedPrereqs->isNotEmpty()) {
                    $satisfied = DB::table('subjects')
                        ->whereIn('id', $placedPrereqs->keys())
                        ->select('id', 'code', 'name')
                        ->get()
                        ->map(function ($s) use ($placedPrereqs) {
                            $loc = $placedPrereqs[$s->id];
                            return "- {$s->code} — {$s->name} (Placed: Year {$loc->year_level}, Term {$loc->term_no})";
                        })
                        ->implode("\n");

                    $satisfiedLines = "\n\nSatisfied Prerequisites (already placed earlier):\n{$satisfied}";
                }

                $msg =
                    "Unable to add subject to {$termLabel}.\n\n" .
                    "Selected Subject: {$selected->code} — {$selected->name}\n" .
                    "Reason: Prerequisite requirement not satisfied.\n\n" .
                    "Missing Prerequisite(s) — must be placed in an earlier term:\n{$missingLines}" .
                    $satisfiedLines . "\n\n" .
                    "Action Required:\n" .
                    "1) Add the missing prerequisite subjects to earlier terms.\n" .
                    "2) Then return to {$termLabel} and add {$selected->code} again.";

                return back()
                    ->withErrors(['subject_id' => $msg], 'addSubject')
                    ->withInput()
                    ->with('flash_term_id', $term->id)
                    ->with('open_term_modal', $term->id);
            }
        }

        // 3) CREATE — safe to add
        $cts = $term->subjects()->create([
            'subject_id' => $data['subject_id'],
            'unit'       => $data['unit'],
            'type'       => $data['type'],
        ]);

        return back()
            ->with('flash_term_id', $term->id)
            ->with('success_addSubject', [
                'title' => '',
                'term'  => [
                    'id' => $term->id,
                    'label' => $termLabel,
                ],
                'cts_id' => $cts->id,
                'subject' => [
                    'id'   => $selected->id ?? $data['subject_id'],
                    'code' => $selected->code ?? null,
                    'name' => $selected->name ?? null,
                ],
                'unit' => (float) $data['unit'],
                'type' => $this->normalizeType($data['type'] ?? null),
                'message' => 'The subject was placed successfully.',
            ]);
    }

    public function destroy(CurriculumTerm $term, CurriculumTermSubject $cts)
    {
        $termLabel = $this->termLabel($term);

        // Load subject info BEFORE deleting (for detailed messaging)
        $subject = $cts->subject()->select('id', 'code', 'name')->first();

        $payload = [
            'term' => [
                'id' => $term->id,
                'label' => $termLabel,
            ],
            'cts_id' => $cts->id,
            'subject' => [
                'id'   => $subject->id ?? null,
                'code' => $subject->code ?? null,
                'name' => $subject->name ?? null,
            ],
            'unit' => (float) $cts->unit,
            'type' => $this->normalizeType($cts->type),
        ];

        // If there are class offerings using this subject, block deletion
        if ($cts->classOfferings()->exists()) {
            return back()
                ->withErrors([
                    'remove' => 'Cannot remove this subject because there are class offerings using it. Cancel/delete those classes first.',
                ], 'removeSubject')
                ->with('flash_term_id', $term->id)
                ->with('flash_cts_id', $cts->id)
                ->with('failed_removeSubject', $payload);
        }

        $cts->delete();

        return back()
            ->with('flash_term_id', $term->id)
            ->with('success_removeSubject', array_merge($payload, [
                'title' => '',
                'message' => 'The subject was removed from this term.',
            ]));
    }
}
