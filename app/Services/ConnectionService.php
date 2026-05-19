<?php

namespace App\Services;

use App\Models\Connection;
use App\Models\Follow;
use App\Models\User;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ConnectionService
{
    public function getConnections(User $user): Collection
    {
        return Connection::where('status', 'accepted')
            ->where(function ($q) use ($user) {
                $q->where('requester_id', $user->id)->orWhere('receiver_id', $user->id);
            })
            ->with(['requester', 'receiver', 'latestMessage'])
            ->get();
    }

    public function getPendingRequests(User $user): Collection
    {
        return Connection::where('receiver_id', $user->id)
            ->where('status', 'pending')
            ->with('requester')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function sendConnectionRequest(User $me, int $receiverId): Connection
    {
        if ($me->id === $receiverId) {
            throw new HttpException(400, 'Cannot connect with yourself');
        }

        // Check existing connection or pending
        $existing = Connection::where(function ($q) use ($me, $receiverId) {
            $q->where(['requester_id' => $me->id, 'receiver_id' => $receiverId])
              ->orWhere(['requester_id' => $receiverId, 'receiver_id' => $me->id]);
        })->first();

        if ($existing) {
            throw new HttpException(409, 'Connection request already exists');
        }

        $receiver = User::findOrFail($receiverId);
        if ($receiver->hasRole('Admin')) {
            throw new HttpException(403, 'Cannot connect with an Administrator');
        }

        if ($receiver->hasRole('Mentor') || $me->hasRole('Mentor')) {
            throw new HttpException(403, 'Connections with mentors are managed in the Mentorship Hub only');
        }

        $type = 'student_student';

        $conn = Connection::create([
            'requester_id' => $me->id,
            'receiver_id'  => $receiverId,
            'type'         => $type,
            'status'       => 'pending',
        ]);

        app(\App\Services\NotificationService::class)->createNotification(
            $receiverId,
            'connection_request',
            [
                'title' => 'New Connection Request',
                'body' => "{$me->first_name} {$me->last_name} sent you a connection request.",
                'sender_id' => $me->id,
                'sender_name' => "{$me->first_name} {$me->last_name}",
                'reference_id' => $conn->id,
            ]
        );

        return $conn;
    }

    public function respondToRequest(User $user, int $id, string $action): Connection
    {
        $conn = Connection::where('receiver_id', $user->id)
            ->where('id', $id)
            ->where('status', 'pending')
            ->firstOrFail();

        $conn->update(['status' => $action === 'accept' ? 'accepted' : 'rejected']);

        if ($action === 'accept') {
            app(\App\Services\NotificationService::class)->createNotification(
                $conn->requester_id,
                'connection_accepted',
                [
                    'title' => 'Connection Request Accepted',
                    'body' => "{$user->first_name} {$user->last_name} accepted your connection request.",
                    'sender_id' => $user->id,
                    'sender_name' => "{$user->first_name} {$user->last_name}",
                    'reference_id' => $conn->id,
                ]
            );
        }

        return $conn->fresh(['requester']);
    }

    public function disconnect(User $me, int $userId): void
    {
        $conn = Connection::where('status', 'accepted')
            ->where(function ($q) use ($me, $userId) {
                $q->where(['requester_id' => $me->id, 'receiver_id' => $userId])
                  ->orWhere(['requester_id' => $userId, 'receiver_id' => $me->id]);
            })->firstOrFail();

        $conn->delete();
    }

    public function follow(User $me, int $userId): void
    {
        if ($me->id === $userId) {
            throw new HttpException(400, 'Cannot follow yourself');
        }

        User::findOrFail($userId);

        Follow::firstOrCreate(['follower_id' => $me->id, 'following_id' => $userId]);
    }

    public function unfollow(User $me, int $userId): void
    {
        Follow::where(['follower_id' => $me->id, 'following_id' => $userId])->delete();
    }

    public function getFollowers(User $user): Collection
    {
        return Follow::where('following_id', $user->id)
            ->with('follower:id,first_name,last_name,profile_photo_url,bio')
            ->get()
            ->pluck('follower');
    }

    public function discover(User $me): array
    {
        $mentors = $me->getRecommendedMentors();
        $peers   = $me->getRecommendedPeers();

        $augment = function ($users) use ($me) {
            return $users->map(function ($u) use ($me) {
                $u->is_connected    = $me->isConnectedTo($u->id);
                $u->is_following    = $me->isFollowing($u->id);
                $u->pending_request = $me->hasPendingConnectionWith($u->id);
                return $u;
            });
        };

        return [
            'recommended_mentors' => $augment($mentors),
            'recommended_peers'   => $augment($peers),
        ];
    }
}
