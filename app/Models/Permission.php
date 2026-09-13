<?php

namespace App\Models;

class Permission extends CampusModel
{
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}
