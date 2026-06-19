<?php

// app/Models/ClassOffering.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassOffering extends Model
{
    protected $fillable = [
        'section_id','curriculum_term_subject_id','start_date','end_date','status','archived_at','created_by','updated_by'
    ];

    public function meetings() { return $this->hasMany(ClassMeeting::class); }

    public function meetingsAll()
{
    return $this->hasMany(\App\Models\ClassMeeting::class, 'class_offering_id');
}


      public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function curriculumTermSubject()
    {
        return $this->belongsTo(CurriculumTermSubject::class, 'curriculum_term_subject_id');
    }

    public function studentEnrollments()
    {
        return $this->hasMany(StudentClassEnrollment::class, 'class_offering_id');
    }

    public function students()
    {
        return $this->belongsToMany(
            User::class,
            'student_class_enrollments',
            'class_offering_id',
            'user_id'
        )->withPivot(['status', 'is_additional'])
         ->withTimestamps();
    }

    public function finalization()
{
    return $this->hasOne(\App\Models\ClassOfferingFinalization::class);
}

public function isFinalized(): bool
{
    return $this->finalization
        && $this->finalization->finalized_at
        && is_null($this->finalization->unlocked_at);
}


}
