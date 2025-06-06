<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /**
         * @var User $this
         */
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'surname'    => $this->surname,
            'email'      => $this->email,
            'avatar_url' => $this->getFirstMediaUrl('avatars'),
            'is_admin'   => $this->hasRole('administrator'),
            'created_at' => $this->created_at->format('d.m.Y H:i'),
            'updated_at' => $this->updated_at->format('d.m.Y H:i'),
        ];
    }
}
