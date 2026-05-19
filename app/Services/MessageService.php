<?php

namespace App\Services;

use App\Models\Connection;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MessageService
{
    public function getConversations(User $me): Collection
    {
        return Connection::where('status', 'accepted')
            ->where(function ($q) use ($me) {
                $q->where('requester_id', $me->id)->orWhere('receiver_id', $me->id);
            })
            ->with(['requester:id,first_name,last_name,profile_photo_url', 'receiver:id,first_name,last_name,profile_photo_url', 'latestMessage.sender'])
            ->get()
            ->sortByDesc(fn($c) => optional($c->latestMessage)->created_at)
            ->values();
    }

    public function getMessages(User $me, int $connectionId): LengthAwarePaginator
    {
        $conn = $this->verifyConnection($connectionId, $me->id);

        // Mark unread messages as read
        $conn->messages()
            ->where('sender_id', '!=', $me->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $conn->messages()
            ->with('sender:id,first_name,last_name,profile_photo_url')
            ->orderBy('created_at')
            ->paginate(50);
    }

    public function sendMessage(User $me, int $connectionId, string $content): Message
    {
        $conn = $this->verifyConnection($connectionId, $me->id);

        return Message::create([
            'connection_id' => $conn->id,
            'sender_id'     => $me->id,
            'content'       => $content,
        ]);
    }

    public function getTotalUnread(User $me): int
    {
        $connIds = Connection::where('status', 'accepted')
            ->where(fn($q) => $q->where('requester_id', $me->id)->orWhere('receiver_id', $me->id))
            ->pluck('id');

        return Message::whereIn('connection_id', $connIds)
            ->where('sender_id', '!=', $me->id)
            ->whereNull('read_at')
            ->count();
    }

    private function verifyConnection(int $connectionId, int $userId): Connection
    {
        return Connection::where('id', $connectionId)
            ->where('status', 'accepted')
            ->where(function ($q) use ($userId) {
                $q->where('requester_id', $userId)->orWhere('receiver_id', $userId);
            })
            ->firstOrFail();
    }
}
