<?php

namespace App\Models;

class BlogPost extends CampusModel
{
    protected $casts = ['published_at' => 'datetime', 'is_published' => 'boolean'];
    public function category() { return $this->belongsTo(BlogCategory::class); }
    public function author() { return $this->belongsTo(User::class); }
    public function scopePublished($query) { return $query->where('is_published', true)->whereNotNull('published_at')->where('published_at', '<=', now()); }
}
