<?php
// app/Models/Curriculum.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Curriculum extends Model {
  protected $fillable = ['program_id','code','title','effective_from','effective_to','is_active'];
  public function program(){ return $this->belongsTo(Program::class); }         // programs.program_name
  public function terms(){ return $this->hasMany(CurriculumTerm::class)->orderBy('sequence'); }
}
