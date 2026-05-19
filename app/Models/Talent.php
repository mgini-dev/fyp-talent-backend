<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Talent extends Model
{
    protected $table = 'talents'; // Explicitly defining because 'talent' was singular in migration command

    protected $fillable = ['name', 'category', 'description'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'talent_user')
            ->withPivot('proficiency', 'portfolio_url')
            ->withTimestamps();
    }
}
