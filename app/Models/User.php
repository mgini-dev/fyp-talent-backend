<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'first_name', 'last_name', 'email', 'phone', 'bio',
        'profile_photo_url', 'status', 'password',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ── RBAC ─────────────────────────────────────────────────────────────────

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole($roleName)
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    public function hasPermissionTo($permissionName)
    {
        foreach ($this->roles as $role) {
            if ($role->permissions()->where('name', $permissionName)->exists()) {
                return true;
            }
        }
        return false;
    }

    public function getAllPermissions()
    {
        $permissions = [];
        foreach ($this->roles()->with('permissions')->get() as $role) {
            foreach ($role->permissions as $permission) {
                $permissions[] = $permission->name;
            }
        }
        return array_unique($permissions);
    }

    // ── TALENTS ───────────────────────────────────────────────────────────────

    public function talents()
    {
        return $this->belongsToMany(Talent::class, 'talent_user')
            ->withPivot('proficiency', 'portfolio_url')
            ->withTimestamps();
    }

    // ── MENTORSHIP (legacy) ───────────────────────────────────────────────────

    public function mentees()
    {
        return $this->hasMany(Mentorship::class, 'mentor_id');
    }

    public function mentors()
    {
        return $this->hasMany(Mentorship::class, 'mentee_id');
    }

    // ── CONNECTIONS ───────────────────────────────────────────────────────────

    public function sentConnections()
    {
        return $this->hasMany(Connection::class, 'requester_id');
    }

    public function receivedConnections()
    {
        return $this->hasMany(Connection::class, 'receiver_id');
    }

    public function isConnectedTo($userId)
    {
        return Connection::where('status', 'accepted')
            ->where(function ($q) use ($userId) {
                $q->where(['requester_id' => $this->id, 'receiver_id' => $userId])
                  ->orWhere(['requester_id' => $userId, 'receiver_id' => $this->id]);
            })->exists();
    }

    public function connectionWith($userId)
    {
        return Connection::where('status', 'accepted')
            ->where(function ($q) use ($userId) {
                $q->where(['requester_id' => $this->id, 'receiver_id' => $userId])
                  ->orWhere(['requester_id' => $userId, 'receiver_id' => $this->id]);
            })->first();
    }

    public function hasPendingConnectionWith($userId)
    {
        return Connection::where('status', 'pending')
            ->where(function ($q) use ($userId) {
                $q->where(['requester_id' => $this->id, 'receiver_id' => $userId])
                  ->orWhere(['requester_id' => $userId, 'receiver_id' => $this->id]);
            })->exists();
    }

    // ── FOLLOWS ───────────────────────────────────────────────────────────────

    public function followings()
    {
        return $this->hasMany(Follow::class, 'follower_id');
    }

    public function followers()
    {
        return $this->hasMany(Follow::class, 'following_id');
    }

    public function isFollowing($userId)
    {
        return Follow::where(['follower_id' => $this->id, 'following_id' => $userId])->exists();
    }

    // ── POSTS ─────────────────────────────────────────────────────────────────

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    // ── MESSAGES ──────────────────────────────────────────────────────────────

    public function messages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    // ── PROJECTS ──────────────────────────────────────────────────────────────

    public function ownProjects()
    {
        return $this->hasMany(Project::class, 'student_id');
    }

    public function mentoredProjects()
    {
        return $this->hasMany(Project::class, 'mentor_id');
    }

    // ── SMART RECOMMENDATIONS ─────────────────────────────────────────────────

    public function getRecommendedMentors($limit = 6)
    {
        $myTalentIds = $this->talents()->pluck('talents.id');

        return User::whereHas('roles', fn($q) => $q->where('name', 'Mentor'))
            ->whereHas('talents', fn($q) => $q->whereIn('talents.id', $myTalentIds))
            ->where('id', '!=', $this->id)
            ->where('status', 'active')
            ->with('talents:id,name,category')
            ->limit($limit)
            ->get();
    }

    public function getRecommendedPeers($limit = 6)
    {
        $myTalentIds = $this->talents()->pluck('talents.id');

        return User::whereHas('roles', fn($q) => $q->where('name', 'Student'))
            ->whereHas('talents', fn($q) => $q->whereIn('talents.id', $myTalentIds))
            ->where('id', '!=', $this->id)
            ->where('status', 'active')
            ->with('talents:id,name,category')
            ->limit($limit)
            ->get();
    }
}
