<?php
// app/Models/Section.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
  protected $fillable = [
    'program_id','curriculum_id','year_level','term_no','name','capacity','status','created_by'
  ];

  // --- Scopes ---
  public function scopeActive($q)   { return $q->where('status','active'); }
  public function scopeArchived($q) { return $q->where('status','archived'); }

  public function scopeSearch($q, $term)
  {
    if (!blank($term)) {
      $q->where('name','like','%'.$term.'%');
    }
    return $q;
  }

  // Relations (optional, for completeness)
  public function program(){ return $this->belongsTo(Program::class); }
  public function curriculum(){ return $this->belongsTo(Curriculum::class); }
  public function creator(){ return $this->belongsTo(User::class,'created_by'); }
}
