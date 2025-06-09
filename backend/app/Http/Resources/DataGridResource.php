<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DataGridResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'name'                  => $this->name,
            'description'           => $this->description,
            'is_active'             => $this->is_active,
            'records_count'         => $this->records_count ?? 0,
            'created_at'            => $this->created_at?->format('d.m.Y'),
            'updated_at'            => $this->updated_at?->format('d.m.Y H:i'),
            'created_at_full'       => $this->created_at?->format('d.m.Y H:i:s'),
            'updated_at_full'       => $this->updated_at?->format('d.m.Y H:i:s'),
            'created_at_iso'        => $this->created_at?->toISOString(),
            'updated_at_iso'        => $this->updated_at?->toISOString(),
            'image_url'             => $this->when($this->relationLoaded('media') && $this->getFirstMedia('data_grid_image'),
                fn() => $this->getFirstMedia('data_grid_image')?->getUrl()
            ),
            'image_id'              => $this->image_id,
            'user_id'               => $this->user_id,

            // Информация о владельце (для общих таблиц)
            'owner_name'            => $this->when(isset($this->owner_name), $this->owner_name),

            // Права доступа пользователя
            'is_owner'              => $this->when(isset($this->is_owner), $this->is_owner),
            'permissions'           => $this->when(isset($this->permissions), $this->permissions),

            // Дополнительные поля для фильтрации
            'has_image'             => !is_null($this->image_id),
            'is_recent'             => $this->created_at?->isAfter(now()->subWeek()) ?? false,
            'is_updated_recently'   => $this->updated_at?->isAfter(now()->subWeek()) ?? false,
            'is_updated_this_month' => $this->updated_at?->isAfter(now()->subMonth()) ?? false,
            'is_updated_today'      => $this->updated_at?->isAfter(now()->subDay()) ?? false,

            // Записи (если загружены)
            'records'               => $this->when(
                $this->relationLoaded('records'),
                fn() => DataGridRecordResource::collection($this->records)
            ),

            // Медиа (если загружены)
            'media'                 => $this->when(
                $this->relationLoaded('media'),
                fn() => $this->media
            ),
        ];
    }
}
