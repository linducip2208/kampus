<?php

namespace App\Models;

class IntegrationLog extends CampusModel
{
    protected $casts = ['payload' => 'array', 'success' => 'boolean'];

    public function endpoint()
    {
        return $this->belongsTo(IntegrationEndpoint::class, 'integration_endpoint_id');
    }
}
