<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;

class ApprovalRequest extends CampusModel
{
    public function workflow() { return $this->belongsTo(ApprovalWorkflow::class, 'approval_workflow_id'); }
    public function requestedBy() { return $this->belongsTo(User::class, 'requested_by'); }
    public function actions() { return $this->hasMany(ApprovalAction::class); }
    public function approvable(): MorphTo { return $this->morphTo(); }
}
