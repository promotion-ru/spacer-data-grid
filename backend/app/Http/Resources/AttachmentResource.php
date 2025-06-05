<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /**
         * @var Media $this
         */
        return [
            'id'         => $this->id,
            'name'       => $this->name,      // Имя медиа-записи (можно редактировать)
            'file_name'  => $this->file_name, // Оригинальное имя файла
            'url'        => $this->getUrl(),
            'mime_type'  => $this->mime_type,
            'size'       => $this->size,                // Размер в байтах
            'created_at' => $this->created_at->format('d.m.Y H:i'),
        ];
    }
}
