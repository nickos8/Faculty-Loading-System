<?php

// app/Models/ClassMeeting.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassMeeting extends Model
{
    protected $fillable = [
        'class_offering_id','day_of_week','time_start','time_end','teacher_id','room_id','created_by','updated_by'
    ];

    public function offering()
    {
        // IMPORTANT: specify the correct foreign key
        return $this->belongsTo(ClassOffering::class, 'class_offering_id');
    }

      public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id')
            ->select(['id','first_name','last_name']);
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id')
            ->select(['id','name']);
    }
}
