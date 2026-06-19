<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Program;
use App\Models\Section;
use App\Models\StudentAcademic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentApprovalController extends Controller
{
    public function approveFreshman(Request $req)
    {
        $data = $req->validate([
            'user_id' => ['required','integer','exists:users,id'],
        ]);

        try {
            DB::beginTransaction();

            // 1) Lock the user row to avoid double-approval
            $user = User::lockForUpdate()->findOrFail($data['user_id']);

            // Basic sanity checks (adjust to your fields)
            if ($user->status !== 'pending') {
                throw ValidationException::withMessages(['student' => 'Student is not pending approval.']);
            }
            if (!$user->program_id) {
                throw ValidationException::withMessages(['student' => 'Student has no program selected.']);
            }

            // 2) Avoid duplicates
            if (StudentAcademic::where('user_id', $user->id)->exists()) {
                throw ValidationException::withMessages(['student' => 'Student already has an academic record.']);
            }

            // 3) Program must have present curriculum
            $program = Program::lockForUpdate()->findOrFail($user->program_id);
            if (!$program->curriculum_id) {
                throw ValidationException::withMessages(['program' => 'Program has no present curriculum.']);
            }

            // 4) Find ACTIVE Y1/T1 section with available seats (capacity only; rooms ignored)
            //    We pick the least-filled to balance load
            $candidate = Section::where([
                    'program_id'    => $program->id,
                    'curriculum_id' => $program->curriculum_id,
                    'year_level'    => 1,
                    'term_no'       => 1,
                    'status'        => 'active',
                ])
                ->whereRaw("(SELECT COUNT(*) FROM student_academics sa
                             WHERE sa.section_id = sections.id
                               AND sa.enrollment_status = 'enrolled') < sections.capacity")
                ->orderByRaw("(SELECT COUNT(*) FROM student_academics sa
                               WHERE sa.section_id = sections.id
                                 AND sa.enrollment_status = 'enrolled') ASC")
                ->orderBy('name')
                ->lockForUpdate() // lock the chosen section row
                ->first();

            if (!$candidate) {
                throw ValidationException::withMessages([
                    'section' => 'No available Year 1 • Term 1 section. Create a new section or increase capacity.'
                ]);
            }

            // safety re-check: seats still free?
            $used = StudentAcademic::where('section_id', $candidate->id)
                ->where('enrollment_status', 'enrolled')
                ->lockForUpdate()
                ->count();

            if ($used >= $candidate->capacity) {
                throw ValidationException::withMessages([
                    'section' => 'Section just filled up. Try again or add capacity.'
                ]);
            }

            // 5) Create placement snapshot
            StudentAcademic::create([
                'user_id'           => $user->id,
                'program_id'        => $program->id,
                'curriculum_id'     => $program->curriculum_id,
                'section_id'        => $candidate->id,
                'enrollment_status' => 'enrolled',
                'status'            => 'regular',
            ]);

            // 6) Mark user as approved/active (adjust to your workflow)
            $user->status = 'active';
            $user->save();

            DB::commit();
            return back()->with('status', 'Approved. Assigned to section: '.$candidate->name);

        } catch (ValidationException $e) {
            DB::rollBack();
            return back()->withErrors($e->errors());
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return back()->withErrors(['error' => 'Unexpected error during approval.']);
        }
    }
}

