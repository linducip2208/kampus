<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Assignment extends CampusModel
{
    use SoftDeletes;
    protected $casts = ['opens_at' => 'datetime', 'due_at' => 'datetime', 'allow_late' => 'boolean', 'max_score' => 'decimal:2'];
    public function classSection() { return $this->belongsTo(ClassSection::class); }
    public function submissions() { return $this->hasMany(AssignmentSubmission::class); }
}
