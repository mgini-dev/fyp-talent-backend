<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MentorshipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (is_null($this->resource)) {
            return [];
        }

        return [
            'id' => $this->id,
            'mentor_id' => $this->mentor_id,
            'mentee_id' => $this->mentee_id,
            'status' => $this->status,
            'goals' => $this->goals,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'mentor' => new UserResource($this->whenLoaded('mentor')),
            'mentee' => new UserResource($this->whenLoaded('mentee')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
