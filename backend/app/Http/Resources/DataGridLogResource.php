<?php

namespace App\Http\Resources;

use App\Models\DataGridLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DataGridLogResource extends JsonResource
{
    /**
     * @var DataGridLog $this
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'action'         => $this->action,
            'description'    => $this->description,
            'user_name'      => $this->user->name ?? 'Система',
            'target_user_id' => $this->target_user_id,
            'created_at'     => $this->created_at->format('d.m.Y H:i:s'),
            'action_badge'   => $this->action_badge,
            'changes'        => $this->formatted_changes,
            'metadata'       => $this->metadata,
            'user'           => new UserResource($this->whenLoaded('user')),
            'target_user'    => new UserResource($this->whenLoaded('target_user')),
        ];
    }
}
