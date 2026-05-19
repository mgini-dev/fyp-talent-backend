<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostComment extends Model
{
    protected $fillable = ['post_id', 'user_id', 'content', 'parent_id'];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id')
            ->select('id', 'first_name', 'last_name', 'profile_photo_url');
    }

    public function replies()
    {
        return $this->hasMany(PostComment::class, 'parent_id')->with('author')->orderBy('created_at');
    }

    public function parent()
    {
        return $this->belongsTo(PostComment::class, 'parent_id');
    }
}
