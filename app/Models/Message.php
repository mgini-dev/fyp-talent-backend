<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['connection_id', 'sender_id', 'content', 'read_at'];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id')
            ->select('id', 'first_name', 'last_name', 'profile_photo_url');
    }

    public function connection()
    {
        return $this->belongsTo(Connection::class);
    }
}
