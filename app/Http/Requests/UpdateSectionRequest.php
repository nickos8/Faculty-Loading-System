<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\CurriculumTerm;

class UpdateSectionRequest extends FormRequest
{
  public function authorize(): bool { return true; }

  public function rules(): array {
    $section = $this->route('section');

    return [
      'name'       => [
        'sometimes','required','string','max:50',
        Rule::unique('sections','name')
            ->ignore($section?->id)
            ->where(fn($q) => $q->where('program_id', $section?->program_id))
      ],
      'capacity'   => ['sometimes','required','integer','min:1'],
      'status'     => ['sometimes','required','in:active,archived'],
      'year_level' => ['sometimes','required','integer','min:1'],
      'term_no'    => ['sometimes','required','integer','min:1'],
    ];
  }

  public function withValidator($validator): void {
    $validator->after(function($v){
      $section = $this->route('section');
      if (!$section) return;

      // If changing year/term, ensure the pair exists in this section's curriculum
      $y = $this->input('year_level');
      $t = $this->input('term_no');

      if ($y !== null && $t !== null) {
        $ok = CurriculumTerm::where([
          'curriculum_id' => $section->curriculum_id,
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
