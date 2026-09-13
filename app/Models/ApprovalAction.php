<?php

namespace App\Models;

class ApprovalAction extends CampusModel
{
    protected $casts = ['acted_at' => 'datetime'];
    public function request() { return $this->belongsTo(ApprovalRequest::class, 'approval_request_id'); }
    public function step() { return $this->belongsTo(ApprovalStep::class, 'approval_step_id'); }
    public function actedBy() { return $this->belongsTo(User::class, 'acted_by'); }
}
