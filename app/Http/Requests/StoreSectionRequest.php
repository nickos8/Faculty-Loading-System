<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\{Program, CurriculumTerm};

class StoreSectionRequest extends FormRequest
{
  public function authorize(): bool { return true; }

  public function rules(): array {
    return [
      'name'       => [
        'required','string','max:50',
        // unique name inside the user's program
        Rule::unique('sections','name')->where(fn($q) => $q->where('program_id', $this->user()?->program_id))
      ],
      'capacity'   => ['required','integer','min:1'],
      'year_level' => ['nullable','integer','min:1'],
      'term_no'    => ['nullable','integer','min:1'],
    ];
  }

  public function withValidator($validator): void {
    $validator->after(function($v){
      $user = $this->user();
      if (!$user || !$user->program_id) {
        $v->errors()->add('program', "You don’t belong to a program.");
        return;
      }

      $program = Program::select('id','curriculum_id')->find($user->program_id);
      if (!$program || !$program->curriculum_id) {
        $v->errors()->add('curriculum', 'This program has no present curriculum configured.');
        return;
      }

      // If year/term provided, verify the pair exists in curriculum_terms for this curriculum
      $y = $this->input('year_level');
      $t = $this->input('term_no');

      if ($y && $t) {
        $ok = CurriculumTerm::where([
          'curriculum_id' => $program->curriculum_id,
          'year_level'    => $y,
          'term_no'       => $t,
        ])->exists();

        if (!$ok) {
          $v->errors()->add('term_no', 'Selected year/term is not in this curriculum.');
        }
      }
    });
  }
}
