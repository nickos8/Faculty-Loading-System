<?php // app/Models/CurriculumTermSubject.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CurriculumTermSubject extends Model {
  protected $fillable = [
    'curriculum_term_id',
    'subject_id',
    'unit',
    'type',
    'min_grade',
    'is_required',
    'note',
];
  public function term(){ return $this->belongsTo(CurriculumTerm::class,'curriculum_term_id'); }
  public function subject(){ return $this->belongsTo(Subject::class); } // subjects.code, subjects.name, subjects.units
  // Units for this subject in this curriculum term
public function getUnitsAttribute(): int
{
    return (int) $this->unit;
}

// Subject type (major/minor/etc.) for this curriculum term
public function getSubjectTypeAttribute(): ?string
{
    return $this->type;
}

 public function classOfferings()
    {
        return $this->hasMany(ClassOffering::class, 'curriculum_term_subject_id');
    }

 protected static function booted()
    {
        static::created(function (CurriculumTermSubject $cts) {
            // When a new subject is added to a curriculum,
            // sync it to all existing students in that curriculum.
            \App\Models\StudentCurriculumSubject::syncForNewCurriculumTermSubject($cts);
        });
    }

}
