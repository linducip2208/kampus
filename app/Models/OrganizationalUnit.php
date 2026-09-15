<?php

namespace App\Models;

class OrganizationalUnit extends CampusModel
{
    public function university()
    {
        return $this->belongsTo(University::class);
    }

    public function parent()
    {
        return $this->belongsTo(OrganizationalUnit::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(OrganizationalUnit::class, 'parent_id');
    }

    public function head()
    {
        return $this->belongsTo(Employee::class, 'head_id');
    }

    public function positions()
    {
        return $this->hasMany(Position::class, 'unit_id');
    }
}
