<?php

namespace App\Models;

class ApprovalStep extends CampusModel
{
    public function workflow() { return $this->belongsTo(ApprovalWorkflow::class, 'approval_workflow_id'); }
    public function actions() { return $this->hasMany(ApprovalAction::class); }
}
