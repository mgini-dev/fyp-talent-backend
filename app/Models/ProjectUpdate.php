<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectUpdate extends Model
{
    protected $fillable = ['project_id', 'user_id', 'content'];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id')
            ->select('id', 'first_name', 'last_name', 'profile_photo_url');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
