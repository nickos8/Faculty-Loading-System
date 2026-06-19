<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentClassEnrollment extends Model
{
    protected $table = 'student_class_enrollments';

    protected $fillable = [
        'user_id',
        'class_offering_id',
        'status',
        'is_additional',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function classOffering(): BelongsTo
    {
        return $this->belongsTo(ClassOffering::class, 'class_offering_id');
    }
}
