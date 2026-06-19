<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassOfferingFinalization extends Model
{
    protected $fillable = [
        'class_offering_id',
        'finalized_at',
        'finalized_by',
        'unlocked_at',
        'unlocked_by',
        'unlock_reason',
    ];

    protected $casts = [
        'finalized_at' => 'datetime',
        'unlocked_at'  => 'datetime',
    ];

    public function classOffering()
    {
        return $this->belongsTo(ClassOffering::class);
    }

    public function finalizedBy()
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function unlockedBy()
    {
        return $this->belongsTo(User::class, 'unlocked_by');
    }

    public function scopeLocked($q)
    {
        return $q->whereNotNull('finalized_at')->whereNull('unlocked_at');
    }
}

