<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public static $wrap = 'task';

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'is_public' => $this->is_public,
            'is_done' => $this->is_done,
            'expired_at' => $this->created_at,
            'created_user_id' => $this->created_user_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_user' => new UserResource($this->createdUser),
            'assigned_users' => UserResource::collection($this->assignedUsers),
        ];
    }
}
