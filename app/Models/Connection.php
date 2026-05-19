<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Connection extends Model
{
    protected $fillable = ['requester_id', 'receiver_id', 'status', 'type'];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id')->select('id', 'first_name', 'last_name', 'profile_photo_url', 'bio');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id')->select('id', 'first_name', 'last_name', 'profile_photo_url', 'bio');
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }
}
