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
            'id'             => $this->id,
            'name'           => $this->name,
            'label'        => $this->name . ($this->is_global ? ' (Глобальный)' : ''),
            'is_global'    => $this->is_global,
            'data_grid_id'   => $this->data_grid_id,
            'data_grid_name' => $this->whenLoaded('dataGrid', fn() => $this->dataGrid->name),
            'created_by'     => $this->created_by,
            'creator_name'   => $this->whenLoaded('creator', function () {
                return $this->creator ? trim($this->creator->name . ' ' . $this->creator->surname) : null;
            }),
            'creator'        => new UserResource($this->whenLoaded('creator')),
            'records_count'  => $this->whenCounted('dataGridRecords'),
            'created_at'     => $this->created_at?->toISOString(),
            'updated_at'     => $this->updated_at?->toISOString(),
        ];
    }
}
