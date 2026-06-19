<?php
// app/Models/CurriculumTerm.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CurriculumTerm extends Model
{
    protected $fillable = [
        'curriculum_id','year_level','term_no','term_type','sequence',
        'start_date','end_date',

    ];
    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];
    public function curriculum(){ return $this->belongsTo(Curriculum::class); }
    public function subjects(){ return $this->hasMany(CurriculumTermSubject::class); }
}
