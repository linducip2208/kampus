<?php

namespace App\Models;

class SystemBackup extends CampusModel
{
    protected $casts = ['started_at' => 'datetime', 'finished_at' => 'datetime'];
}
