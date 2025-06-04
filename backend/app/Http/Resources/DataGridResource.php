<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DataGridResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'image_url'   => $this->getFirstMediaUrl('data_grid_image'),
            'is_active'   => $this->is_active,
            'user'        => $this->user->toArray(),
            'created_at'  => $this->created_at->format('d.m.Y H:i'),
            'updated_at'  => $this->updated_at->format('d.m.Y H:i'),
        ];
    }
}
