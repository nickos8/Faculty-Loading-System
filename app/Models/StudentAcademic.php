<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentAcademic extends Model
{
    protected $fillable = [
        'user_id','program_id','curriculum_id','section_id',
        'enrollment_status','status',
    ];

    public function student()   { return $this->belongsTo(\App\Models\User::class, 'user_id'); }
    public function program()   { return $this->belongsTo(\App\Models\Program::class); }
    public function curriculum(){ return $this->belongsTo(\App\Models\Curriculum::class); }
    public function section()   { return $this->belongsTo(\App\Models\Section::class); }

        public function curriculumSubjects()
    {
        return $this->hasMany(StudentCurriculumSubject::class, 'student_academic_id');
    }

    public function customCurriculumSubjects()
{
    return $this->hasMany(CustomStudentCurriculumSubject::class);
}


    // handy query helpers
    public function scopeEnrolled($q)  { return $q->where('enrollment_status','enrolled'); }
    public function scopeRegular($q)   { return $q->where('status','regular'); }
    public function scopeIrregular($q) { return $q->where('status','irregular'); }
}
