<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

     public function scopeSearch($query, $searchTerm)
    {
        return $query->where('name', 'like', '%' . $searchTerm . '%')
                     ->orWhere('code', 'like', '%' . $searchTerm . '%');
    }

    // Define the fillable fields to allow mass assignment
     protected $fillable = [
        'name','code','created_by','program_id','status'
    ];
    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    protected static function booted()
{
    static::creating(function (Subject $subject) {
        if (auth()->check() && empty($subject->created_by)) {
            $subject->created_by = auth()->id();
        }
    });
}

public function prerequisites()
{
    return $this->belongsToMany(
        \App\Models\Subject::class,
        'subject_prerequisites',
        'subject_id',
        'prerequisite_subject_id'
    )->withTimestamps();
}

public function isPrerequisiteFor()
{
    return $this->belongsToMany(
        \App\Models\Subject::class,
        'subject_prerequisites',
        'prerequisite_subject_id',
        'subject_id'
    )->withTimestamps();
}

public function preferredTeachers()
{
    return $this->belongsToMany(
        User::class,
        'teacher_preferred_subjects',
        'subject_id',
        'teacher_id'
    )->withPivot('preference_level')->withTimestamps();
}

public function teacherPreferredSubjects()
{
    return $this->hasMany(TeacherPreferredSubject::class, 'subject_id');
}


}


