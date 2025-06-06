<?php

namespace App\Http\Resources;

use App\Models\DataGrid;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DataGridResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /**
         * @var DataGrid $this
         */
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'image_url'   => $this->getFirstMediaUrl('data_grid_image'),
            'is_active'   => $this->is_active,
            'is_owner'    => $this->is_owner,
            'permissions' => $this->permissions,
            'owner_name'  => $this->owner_name,
            'user'        => new UserResource($this->whenLoaded('user')),
            'records'     => DataGridRecordResource::collection($this->whenLoaded('records')),
            'created_at'  => $this->created_at->format('d.m.Y H:i'),
            'updated_at'  => $this->updated_at->format('d.m.Y H:i'),
        ];
    }
}
