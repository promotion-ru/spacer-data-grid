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
            'id'              => $this->id,
            'name'            => $this->name,
            'surname'         => $this->surname,
            'email'           => $this->email,
            'avatar_url'      => $this->getFirstMediaUrl('avatars'),
            'roles'           => RoleResource::collection($this->whenLoaded('roles')),
            'permissions'     => PermissionResource::collection($this->whenLoaded('permissions')),
            'all_permissions' => $this->when($this->relationLoaded('roles'), function () {
                return $this->getAllPermissions()->map(function ($permission) {
                    return [
                        'id'         => $permission->id,
                        'name'       => $permission->name,
                        'guard_name' => $permission->guard_name,
                    ];
                });
            }),
            'is_admin'        => $this->when($this->relationLoaded('roles'), function () {
                return $this->hasRole('administrator');
            }),
            'role_names'      => $this->when($this->relationLoaded('roles'), function () {
                return $this->getRoleNames();
            }),
            'created_at'      => $this->created_at->format('d.m.Y H:i'),
            'updated_at'      => $this->updated_at->format('d.m.Y H:i'),
        ];
    }
}
