<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = ['student_id', 'mentor_id', 'title', 'description', 'status', 'talent_id'];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id')
            ->select('id', 'first_name', 'last_name', 'profile_photo_url');
    }

    public function mentor()
    {
        return $this->belongsTo(User::class, 'mentor_id')
            ->select('id', 'first_name', 'last_name', 'profile_photo_url');
    }

    public function talent()
    {
        return $this->belongsTo(Talent::class);
    }

    public function updates()
    {
        return $this->hasMany(ProjectUpdate::class)->with('author')->orderBy('created_at', 'desc');
    }
}
