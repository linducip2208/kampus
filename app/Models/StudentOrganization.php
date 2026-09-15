<?php

namespace App\Models;

class StudentOrganization extends CampusModel
{
    protected $casts = ['is_active' => 'boolean'];

    public function university()
    {
        return $this->belongsTo(University::class);
    }

    public function advisor()
    {
        return $this->belongsTo(LecturerProfile::class, 'advisor_id');
    }

    public function members()
    {
        return $this->hasMany(StudentOrganizationMember::class);
    }

    public function activities()
    {
        return $this->hasMany(StudentActivity::class);
    }
}
