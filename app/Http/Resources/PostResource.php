<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (is_null($this->resource)) {
            return [];
        }

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'content' => $this->content,
            'type' => $this->type,
            'visibility' => $this->visibility,
            'talent_id' => $this->talent_id,
            'attachments' => $this->attachments,
            'likes_count' => $this->likes_count ?? 0,
            'dislikes_count' => $this->dislikes_count ?? 0,
            'comments_count' => $this->comments_count ?? 0,
            'my_reaction' => $this->when(isset($this->my_reaction), $this->my_reaction),
            'author' => new UserResource($this->whenLoaded('author')),
            'talent' => new TalentResource($this->whenLoaded('talent')),
            'comments' => PostCommentResource::collection($this->whenLoaded('comments')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
