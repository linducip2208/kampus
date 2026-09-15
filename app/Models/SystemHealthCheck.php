<?php

namespace App\Models;

class SystemHealthCheck extends CampusModel
{
    protected $casts = ['details' => 'array', 'checked_at' => 'datetime'];
}
