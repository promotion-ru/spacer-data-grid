<?php

namespace App\Http\Resources;

use App\Models\DataGridRecord;
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
            'id'                => $this->id,
            'data_grid_id'      => $this->data_grid_id,
            'name'              => $this->name,
            'operation_type_id' => $this->operation_type_id,
            'type_id'           => $this->type_id,
            'description'       => $this->description,
            'amount'            => $this->amount,
            'creator'           => new UserResource($this->whenLoaded('creator')),
            'attachments'       => AttachmentResource::collection($this->whenLoaded('attachments')),
            'created_at'        => $this->created_at->format('d.m.Y H:i'),
            'updated_at'        => $this->updated_at->format('d.m.Y H:i'),
        ];
    }
}
