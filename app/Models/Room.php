<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'capacity',
        'description',
        'status',
        'daily_start_time',
        'daily_end_time',
    ];

    // Helpers for nice display (e.g., "8:00 AM")
    public function getDailyStartTimeFormattedAttribute(): string
    {
        return \Carbon\Carbon::createFromFormat('H:i:s', $this->daily_start_time)->format('g:i A');
    }

    public function getDailyEndTimeFormattedAttribute(): string
    {
        return \Carbon\Carbon::createFromFormat('H:i:s', $this->daily_end_time)->format('g:i A');
    }
}
