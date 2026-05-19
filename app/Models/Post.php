<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = ['user_id', 'content', 'type', 'visibility', 'talent_id', 'attachments'];

    protected $casts = [
        'attachments' => 'array',
    ];

    protected $appends = ['likes_count', 'dislikes_count', 'comments_count'];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id')
            ->select('id', 'first_name', 'last_name', 'profile_photo_url', 'bio');
    }

    public function talent()
    {
        return $this->belongsTo(Talent::class);
    }

    public function reactions()
    {
        return $this->hasMany(PostReaction::class);
    }

    public function comments()
    {
        return $this->hasMany(PostComment::class)->whereNull('parent_id')->with('author', 'replies.author')->orderBy('created_at');
    }

    public function getLikesCountAttribute()
    {
        return $this->reactions()->where('type', 'like')->count();
    }

    public function getDislikesCountAttribute()
    {
        return $this->reactions()->where('type', 'dislike')->count();
    }

    public function getCommentsCountAttribute()
    {
        return $this->hasMany(PostComment::class)->count();
    }
}
