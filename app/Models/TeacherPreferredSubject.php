<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherPreferredSubject extends Model
{
    protected $fillable = [
        'teacher_id',
        'subject_id',
        'preference_level',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
