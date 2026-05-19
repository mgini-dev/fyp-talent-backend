<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (is_null($this->resource)) {
            return [];
        }

        return [
            'id' => $this->id,
            'connection_id' => $this->connection_id,
            'sender_id' => $this->sender_id,
            'content' => $this->content,
            'read_at' => $this->read_at,
            'sender' => new UserResource($this->whenLoaded('sender')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
