<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (is_null($this->resource)) {
            return [];
        }

        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'mentor_id' => $this->mentor_id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'talent_id' => $this->talent_id,
            'updates_count' => $this->updates_count,
            'student' => new UserResource($this->whenLoaded('student')),
            'mentor' => new UserResource($this->whenLoaded('mentor')),
            'talent' => new TalentResource($this->whenLoaded('talent')),
            'updates' => ProjectUpdateResource::collection($this->whenLoaded('updates')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
