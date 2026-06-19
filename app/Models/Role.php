<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    // ✅ Avoid magic strings everywhere
    public const SUPER_ADMIN   = 'super_admin';
    public const PROGRAM_ADMIN = 'program_admin';
    public const TEACHER       = 'teacher';
    public const STUDENT       = 'student';

    protected $fillable = ['name'];

    /**
     * A role can belong to many users (through user_roles pivot).
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles');
    }
}
 