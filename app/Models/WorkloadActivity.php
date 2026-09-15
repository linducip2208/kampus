<?php

namespace App\Models;

class WorkloadActivity extends CampusModel
{
    protected $casts = ['sks' => 'decimal:2'];

    public function workload()
    {
        return $this->belongsTo(LecturerWorkload::class, 'lecturer_workload_id');
    }
}
