<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherAvailability extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'day',
        'start_time',
        'end_time'
    ];

      public function teacher()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
