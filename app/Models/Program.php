<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    // Allow mass assignment for these attributes
    protected $fillable = [
    'program_code',
    'program_name',
    'description',
    'status',
    'duration',
    'curriculum_version',
    'terms_per_year', // Added terms_per_year to the fillable fields
    'curriculum_id'

];

public function users()
{
    return $this->hasMany(User::class);
}

// Define relationships, such as the inverse of the `adminPrograms` relationship.
    public function admins()
    {
        return $this->belongsToMany(User::class, 'program_admin_assignments', 'program_id', 'user_id');
    }

    // If there are no relationships for now, you don't need to define any methods here.

      // Define the relationship with the Curriculum model
    public function curriculum()
    {
        return $this->belongsTo(Curriculum::class);
    }

     public function sections()
    {
        return $this->hasMany(Section::class, 'program_id', 'id');
    }


}


