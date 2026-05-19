<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConnectionIndexResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (is_null($this->resource)) {
            return [];
        }

        $me = $request->user();
        $other = $this->requester_id === $me->id ? $this->receiver : $this->requester;
        $unread = $this->messages()
            ->where('sender_id', '!=', $me->id)
            ->whereNull('read_at')
            ->count();

        return [
            'connection_id' => $this->id,
            'user'          => new UserResource($other),
            'type'          => $this->type,
            'unread'        => $unread,
            'last_message'  => new MessageResource($this->latestMessage),
        ];
    }
}
