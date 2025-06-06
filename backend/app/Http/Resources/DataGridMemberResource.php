<?php
// app/Http/Resources/DataGridMemberResource.php
namespace App\Http\Resources;

use App\Models\DataGridMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DataGridMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /**
         * @var DataGridMember $this
         */
        return [
            'id'          => $this->id,
            'permissions' => $this->permissions,
            'joined_at'   => $this->created_at->format('d.m.Y H:i'),
            'user'        => [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'email' => $this->user->email,
            ],
            'invited_by'  => [
                'id'    => $this->invitedBy->id,
                'name'  => $this->invitedBy->name,
                'email' => $this->invitedBy->email,
            ],
        ];
    }
}
