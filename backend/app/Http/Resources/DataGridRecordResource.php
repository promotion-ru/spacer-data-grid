<?php

namespace App\Http\Resources;

use App\Http\Resources\DataGridType\DataGridTypeResource;
use App\Models\DataGridRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DataGridRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /**
         * @var DataGridRecord $this
         */
        return [
            'id'                  => $this->id,
            'data_grid_id'        => $this->data_grid_id,
            'name'                => $this->name,
            'date'                => Carbon::parse($this->date)->format('d.m.Y'),
            'operation_type_id'   => $this->operation_type_id,
            'type_id'             => $this->type_id,
            'type_name'           => $this->when($this->relationLoaded('type'), $this->type?->name),
            'description'         => $this->description,
            'amount'              => $this->amount,
            'created_by'          => $this->created_by,

            // Связанные данные
            'creator'             => $this->when($this->relationLoaded('creator'), fn() => new UserResource($this->creator)),
            'type'                => $this->when($this->relationLoaded('type'), fn() => new DataGridTypeResource($this->type)),
            'attachments'         => $this->when($this->relationLoaded('attachments'), fn() => AttachmentResource::collection($this->attachments)),

            // Даты
            'created_at'          => $this->created_at->format('d.m.Y H:i'),
            'updated_at'          => $this->updated_at->format('d.m.Y H:i'),
        ];
    }
}
