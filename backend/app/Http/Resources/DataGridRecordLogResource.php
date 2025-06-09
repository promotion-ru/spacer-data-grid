<?php

namespace App\Http\Resources;

use App\Models\DataGridRecordLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DataGridRecordLogResource extends JsonResource
{
    /**
     * @var DataGridRecordLog $this
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'action'              => $this->action,
            'description'         => $this->description,
            'user_name'           => $this->user->name ?? 'Система',
            'data_grid_record_id' => $this->data_grid_record_id,
            'created_at'          => $this->created_at->format('d.m.Y H:i:s'),
            'action_badge'        => $this->action_badge,
            'changes'             => $this->formatted_changes,
            'metadata'            => $this->metadata,
            'user'                => new UserResource($this->whenLoaded('user')),
        ];
    }
}
