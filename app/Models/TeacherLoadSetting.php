<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherLoadSetting extends Model
{
    protected $fillable = [
        'user_id',
        'employment_type',
        'max_units',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
