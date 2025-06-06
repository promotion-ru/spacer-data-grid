<?php
// app/Http/Resources/DataGridInvitationResource.php
namespace App\Http\Resources;

use App\Models\DataGridInvitation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DataGridInvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /**
         * @var DataGridInvitation $this
         */
        return [
            'id' => $this->id,
            'token' => $this->token,
            'permissions' => $this->permissions,
            'status' => $this->status,
            'created_at' => $this->created_at->format('d.m.Y H:i'),
            'data_grid' => [
                'id' => $this->dataGrid->id,
                'name' => $this->dataGrid->name,
                'description' => $this->dataGrid->description,
                'image_url' => $this->dataGrid->image_url,
                'owner' => $this->dataGrid->user->name,
            ],
            'invited_by' => [
                'id' => $this->invitedBy->id,
                'name' => $this->invitedBy->name,
                'email' => $this->invitedBy->email,
            ],
            'user' => [
                'id' => $this->invitedBy->id,
                'name' => $this->invitedBy->name,
                'email' => $this->invitedBy->email,
            ],
        ];
    }
}
