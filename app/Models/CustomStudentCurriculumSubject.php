<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomStudentCurriculumSubject extends Model
{
    protected $fillable = [
    'student_academic_id',
    'subject_id',
    'display_year_level',
    'display_term_no',
    'status',
    'remarks',
    'source_type',
    'external_school',
    'external_subject_code',
    'external_subject_name',
    'external_units',
    'subject_type',    
    'evaluated_by',
    'evaluated_at',
];


    protected $casts = [
        'evaluated_at' => 'datetime',
    ];

    // relationships
    public function studentAcademic(): BelongsTo
    {
        return $this->belongsTo(StudentAcademic::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }

    // scopes/helpers
    public function scopeForStudent($query, StudentAcademic $academic)
    {
        return $query->where('student_academic_id', $academic->id);
    }

    public function scopeOrderedForDisplay($query)
    {
        return $query
            ->orderByRaw('COALESCE(display_year_level, 99)')
            ->orderByRaw('COALESCE(display_term_no, 99)')
            ->orderBy('id');
    }
}
