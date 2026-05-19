<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (is_null($this->resource)) {
            return [];
        }

        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'bio' => $this->bio,
            'profile_photo_url' => $this->profile_photo_url,
            'status' => $this->status,
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            'talents' => TalentResource::collection($this->whenLoaded('talents')),
            'own_projects' => ProjectResource::collection($this->whenLoaded('ownProjects')),
            'mentored_projects' => ProjectResource::collection($this->whenLoaded('mentoredProjects')),
            'is_connected' => $this->when(isset($this->is_connected), $this->is_connected),
            'is_following' => $this->when(isset($this->is_following), $this->is_following),
            'pending_request' => $this->when(isset($this->pending_request), $this->pending_request),
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
