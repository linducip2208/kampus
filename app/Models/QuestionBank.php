<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class QuestionBank extends CampusModel
{
    use SoftDeletes;

    protected $casts = ['is_active' => 'boolean'];

    public function university()
    {
        return $this->belongsTo(University::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}
