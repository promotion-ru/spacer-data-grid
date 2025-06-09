<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TokenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'abilities'    => $this->abilities,
            'last_used_at' => $this->last_used_at,
            'expires_at'   => $this->expires_at,
            'created_at'   => $this->created_at,
            'is_current'   => $this->when(isset($this->is_current), $this->is_current),
            'is_expired'   => $this->expires_at && $this->expires_at->isPast(),
        ];
    }
}
