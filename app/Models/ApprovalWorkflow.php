<?php

namespace App\Models;

class ApprovalWorkflow extends CampusModel
{
    protected $casts = ['is_active' => 'boolean'];
    public function university() { return $this->belongsTo(University::class); }
    public function steps() { return $this->hasMany(ApprovalStep::class)->orderBy('step_order'); }
    public function requests() { return $this->hasMany(ApprovalRequest::class); }
}
