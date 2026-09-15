<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class DiscussionPost extends CampusModel
{
    use SoftDeletes;

    protected $casts = ['edited_at' => 'datetime'];

    public function discussion()
    {
        return $this->belongsTo(Discussion::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
