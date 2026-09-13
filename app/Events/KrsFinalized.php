<?php

namespace App\Events;

use App\Models\StudyPlan;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KrsFinalized
{
    use Dispatchable, SerializesModels;

    public function __construct(public StudyPlan $studyPlan, public ?string $actorId = null) {}
}
