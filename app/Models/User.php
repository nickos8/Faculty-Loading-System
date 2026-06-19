<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany; // <-- Add this import
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'first_name', 'last_name', 'phone_number','address','gender', 'email', 'password', 'school_id',
        'status', 'approved_by', 'approved_at', 'declined_by', 'declined_at', 'program_id'
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'approved_at'       => 'datetime',
            'declined_at'       => 'datetime',
        ];
    }

    // Relationship with roles
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    // Check if the user has a particular role
    public function hasRole($roleName): bool
    {
        return $this->roles->contains('name', $roleName);
    }

    // Check if the user has any of the provided roles
    public function hasAnyRole(array $roleNames): bool
    {
        foreach ($roleNames as $roleName) {
            if ($this->hasRole($roleName)) {
                return true;
            }
        }
        return false;
    }


    // In app/Models/User.php

public function adminPrograms()
{
    // Many-to-many relationship between User and Programs
    return $this->belongsToMany(Program::class, 'program_admin_assignments', 'user_id', 'program_id');
}

    // Relationship with UserApproval (HasMany)
    public function approvals(): HasMany
    {
        return $this->hasMany(UserApproval::class);
    }

    // Approve the user
    public function approve(User $actor, ?string $note = null)
    {
        // Start a database transaction to ensure both tables are updated
        \DB::transaction(function () use ($actor, $note) {
            // Update the user's status to 'active'
            $this->update([
                'status'      => 'active',
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'declined_by' => null,
                'declined_at' => null,
            ]);

            // Log the approval in the user_approvals table
            $this->approvals()->create([
                'acted_by'  => $actor->id,
                'decision'  => 'approved',
                'note'      => $note ?? 'No note provided',
            ]);
        });
    }

    // Decline the user
    public function decline(User $actor, ?string $note = null)
    {
        // Start a database transaction to ensure both tables are updated
        \DB::transaction(function () use ($actor, $note) {
            // Update the user's status to 'declined'
            $this->update([
                'status'      => 'declined',
                'declined_by' => $actor->id,
                'declined_at' => now(),
                'approved_by' => null,
                'approved_at' => null,
            ]);

            // Log the decline in the user_approvals table
            $this->approvals()->create([
                'acted_by'  => $actor->id,
                'decision'  => 'declined',
                'note'      => $note ?? 'No note provided',
            ]);
        });
    }

        // app/Models/User.php
    public function program()
    {
        return $this->belongsTo(\App\Models\Program::class, 'program_id');
    }

    public function documents()
{
    return $this->hasMany(\App\Models\UserDocument::class);
}

public function studentAcademic()
{
    return $this->hasOne(StudentAcademic::class, 'user_id', 'id');
}

public function classEnrollments()
{
    return $this->hasMany(StudentClassEnrollment::class, 'user_id');
}

public function enrolledClassOfferings()
{
    return $this->belongsToMany(
        ClassOffering::class,
        'student_class_enrollments',
        'user_id',
        'class_offering_id'
    )->withPivot(['status', 'is_additional'])
     ->withTimestamps();
}

public function approvedBy()
{
    return $this->belongsTo(User::class, 'approved_by');
}

   public function declinedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'declined_by');
    }

  public function scopeWithRole($query, string $roleName)
{
    return $query->whereHas('roles', function ($q) use ($roleName) {
        $q->where('name', $roleName);
    });
}

public function teacherAvailabilities()
{
    return $this->hasMany(\App\Models\TeacherAvailability::class, 'user_id');
}

public function teacherLoadSetting()
{
    return $this->hasOne(\App\Models\TeacherLoadSetting::class, 'user_id');
}

public function preferredSubjects()
{
    return $this->belongsToMany(
        Subject::class,
        'teacher_preferred_subjects',
        'teacher_id',
        'subject_id'
    )->withPivot('preference_level')->withTimestamps();
}

public function teacherPreferredSubjects()
{
    return $this->hasMany(TeacherPreferredSubject::class, 'teacher_id');
}

}

