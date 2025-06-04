<?php

return [
    'disk_name' => env('MEDIA_DISK', 'yandex_uploads'),

    'max_file_size' => 1024 * 1024 * 50, // 50MB для Yandex Cloud

    'path_generator' => null,

    'url_generator' => null,

    'conversions_disk' => env('CONVERSIONS_DISK', 'yandex_uploads'),

    'file_namer' => Spatie\MediaLibrary\Support\FileNamer\DefaultFileNamer::class,

    'queue_name' => '',

    'queue_conversions_by_default' => env('QUEUE_CONVERSIONS_BY_DEFAULT', true),

    'media_model' => Spatie\MediaLibrary\MediaCollections\Models\Media::class,

    'remote' => [
        'extra_headers' => [
            'CacheControl' => 'max-age=604800',
        ],
    ],
];
