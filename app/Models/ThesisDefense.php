<?php

namespace App\Models;

class ThesisDefense extends CampusModel
{
    protected $casts = ['scheduled_at' => 'datetime', 'score' => 'decimal:2', 'graded_at' => 'datetime'];

    public function proposal()
    {
        return $this->belongsTo(ThesisProposal::class, 'thesis_proposal_id');
    }

    public function examiners()
    {
        return $this->hasMany(ThesisExaminer::class);
    }

    public function revisions()
    {
        return $this->hasMany(ThesisRevision::class);
    }

    public function gradedBy()
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
