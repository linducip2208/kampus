<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class BlogCategory extends CampusModel
{
    public function posts(): HasMany { return $this->hasMany(BlogPost::class, 'category_id'); }
}
