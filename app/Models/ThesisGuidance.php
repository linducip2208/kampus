<?php

namespace App\Models;

class ThesisGuidance extends CampusModel
{
    protected $casts = ['guided_at' => 'datetime'];

    public function proposal()
    {
        return $this->belongsTo(ThesisProposal::class, 'thesis_proposal_id');
    }
}
