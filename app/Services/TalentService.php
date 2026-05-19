<?php

namespace App\Services;

use App\Models\Talent;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TalentService
{
    public function getTalents(): Collection
    {
        return Talent::all();
    }

    public function attachTalent(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $user->talents()->syncWithoutDetaching([
                $data['talent_id'] => [
                    'proficiency' => $data['proficiency'],
                    'portfolio_url' => $data['portfolio_url'] ?? null
                ]
            ]);

            return $user->load('talents');
        });
    }

    public function removeTalent(User $user, int $talentId): void
    {
        $user->talents()->detach($talentId);
    }

    public function getDirectory(User $me): Collection
    {
        $users = User::where('id', '!=', $me->id)
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['Student', 'Mentor']);
            })
            ->with([
                'talents', 
                'roles', 
                'ownProjects.mentor', 
                'ownProjects.talent', 
                'mentoredProjects.student', 
                'mentoredProjects.talent'
            ])
            ->get();

        $users->map(function ($u) use ($me) {
            $u->is_connected    = $me->isConnectedTo($u->id);
            $u->is_following    = $me->isFollowing($u->id);
            $u->pending_request = $me->hasPendingConnectionWith($u->id);
            return $u;
        });

        return $users;
    }

    public function storeLookup(array $data): Talent
    {
        return DB::transaction(function () use ($data) {
            return Talent::create([
                'name' => $data['name'],
                'category' => $data['category'],
                'description' => $data['description'] ?? null
            ]);
        });
    }

    public function updateLookup(int $id, array $data): Talent
    {
        $talent = Talent::findOrFail($id);

        return DB::transaction(function () use ($talent, $data) {
            $talent->update([
                'name' => $data['name'],
                'category' => $data['category'],
                'description' => $data['description'] ?? null
            ]);

            return $talent;
        });
    }

    public function deleteLookup(int $id): void
    {
        $talent = Talent::findOrFail($id);

        DB::transaction(function () use ($talent) {
            $talent->delete();
        });
    }
}
