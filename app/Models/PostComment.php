<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostComment extends Model
{
    protected $fillable = ['post_id', 'user_id', 'name', 'email', 'content', 'is_approved', 'ip'];

    protected $casts = ['is_approved' => 'boolean'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
