<?php
// app/Http/Resources/DataGridType/DataGridTypeResource.php
namespace App\Http\Resources\DataGridType;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DataGridTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'label'        => $this->name . ($this->is_global ? ' (Глобальный)' : ''),
            'is_global'    => $this->is_global,
            'data_grid_id' => $this->data_grid_id,
            'created_at'   => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'   => $this->updated_at?->format('Y-m-d H:i:s'),
            'creator'      => new UserResource($this->whenLoaded('creator')),
        ];
    }
}
