<?php
// app/Actions/Curriculum/GenerateTermGrid.php
namespace App\Actions\Curriculum;
use App\Models\Curriculum;

class GenerateTermGrid {
  public function __invoke(Curriculum $curriculum): void {
    $program  = $curriculum->program;           // uses programs.duration, terms_per_year
    $seq = 1;
    for ($y=1; $y <= $program->duration; $y++) {
      for ($t=1; $t <= $program->terms_per_year; $t++) {
        $curriculum->terms()->create([
          'year_level'=>$y, 'term_no'=>$t, 'term_type'=>'regular', 'sequence'=>$seq++,
        ]);
      }
    }
    // Optional: summer terms → term_no=0, term_type='summer', sequence increments
  }
}
