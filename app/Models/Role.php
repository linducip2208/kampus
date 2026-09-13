<?php

namespace App\Models;

class Role extends CampusModel
{
    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot([
            'university_id', 'campus_id', 'faculty_id', 'study_program_id',
        ]);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }
}
