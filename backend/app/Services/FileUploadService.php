<?php

namespace App\Services;

use Exception;
use finfo;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class FileUploadService
{
    private array $allowedMimeTypes = [
        // Images
        'image/jpeg'                                                                => 'jpg',
        'image/png'                                                                 => 'png',
        'image/webp'                                                                => 'webp',
        'image/gif'                                                                 => 'gif',
        'image/svg+xml'                                                             => 'svg',
        'image/bmp'                                                                 => 'bmp',
        'image/tiff'                                                                => 'tiff',

        // Documents
        'application/pdf'                                                           => 'pdf',
        'application/msword'                                                        => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
        'application/vnd.ms-excel'                                                  => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
        'application/vnd.ms-powerpoint'                                             => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',

        // Text files
        'text/plain'                                                                => 'txt',
        'text/csv'                                                                  => 'csv',
        'application/json'                                                          => 'json',
        'text/xml'                                                                  => 'xml',
        'application/xml'                                                           => 'xml',

        // Archives
        'application/zip'                                                           => 'zip',
        'application/x-rar-compressed'                                              => 'rar',
        'application/x-7z-compressed'                                               => '7z',
        'application/gzip'                                                          => 'gz',

        // Audio
        'audio/mpeg'                                                                => 'mp3',
        'audio/wav'                                                                 => 'wav',
        'audio/ogg'                                                                 => 'ogg',

        // Video
        'video/mp4'                                                                 => 'mp4',
        'video/avi'                                                                 => 'avi',
        'video/quicktime'                                                           => 'mov',
        'video/webm'                                                                => 'webm',
    ];

    private int $maxFileSize = 10 * 1024 * 1024; // 10MB по умолчанию
    private bool $useTempFile = true;            // По умолчанию используем временные файлы как в оригинале
    private bool $validateSize = true;
    private bool $validateMimeType = true;

    public function __construct()
    {
        // Устанавливаем дефолтные настройки
        $this->useTempFile = true;
    }

    public function useTempFile(bool $use = true): self
    {
        $this->useTempFile = $use;
        return $this;
    }

    public function disableSizeValidation(): self
    {
        $this->validateSize = false;
        return $this;
    }

    public function disableMimeValidation(): self
    {
        $this->validateMimeType = false;
        return $this;
    }

    public function addAllowedMimeType(string $mimeType, string $extension): self
    {
        $this->allowedMimeTypes[$mimeType] = $extension;
        return $this;
    }

    public function onlyImages(): self
    {
        $this->allowedMimeTypes = [
            'image/jpeg'    => 'jpg',
            'image/png'     => 'png',
            'image/webp'    => 'webp',
            'image/gif'     => 'gif',
            'image/bmp'     => 'bmp',
            'image/svg+xml' => 'svg',
            'image/tiff'    => 'tiff',
        ];
        return $this;
    }

    public function onlyDocuments(): self
    {
        $this->allowedMimeTypes = [
            'application/pdf'                                                           => 'pdf',
            'application/msword'                                                        => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
            'application/vnd.ms-excel'                                                  => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
            'application/vnd.ms-powerpoint'                                             => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'text/plain'                                                                => 'txt',
            'text/csv'                                                                  => 'csv',
            'application/json'                                                          => 'json',
            'text/xml'                                                                  => 'xml',
            'application/xml'                                                           => 'xml',
        ];
        return $this;
    }

    public function onlyArchives(): self
    {
        $this->allowedMimeTypes = [
            'application/zip'              => 'zip',
            'application/x-rar-compressed' => 'rar',
            'application/x-7z-compressed'  => '7z',
            'application/gzip'             => 'gz',
            'application/x-tar'            => 'tar',
        ];
        return $this;
    }

    public function onlyAudio(): self
    {
        $this->allowedMimeTypes = [
            'audio/mpeg' => 'mp3',
            'audio/wav'  => 'wav',
            'audio/ogg'  => 'ogg',
            'audio/mp4'  => 'm4a',
            'audio/aac'  => 'aac',
            'audio/flac' => 'flac',
        ];
        return $this;
    }

    public function onlyVideo(): self
    {
        $this->allowedMimeTypes = [
            'video/mp4'       => 'mp4',
            'video/avi'       => 'avi',
            'video/quicktime' => 'mov',
            'video/webm'      => 'webm',
            'video/x-msvideo' => 'avi',
            'video/x-ms-wmv'  => 'wmv',
        ];
        return $this;
    }

    public function onlyAvatarImages(): self
    {
        $this->allowedMimeTypes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];
        return $this;
    }

    public function uploadMultipleFiles(
        HasMedia $model,
        array    $filesData,
        string   $collection = 'default',
        array    $options = []
    ): array
    {
        $uploadedFiles = [];

        foreach ($filesData as $index => $fileData) {
            try {
                $uploadedFiles[] = $this->uploadFile($model, $fileData, $collection, $options);
            } catch (Exception $e) {
                throw new Exception("Ошибка загрузки файла #{$index}: " . $e->getMessage());
            }
        }

        return $uploadedFiles;
    }

    public function uploadFile(
        HasMedia $model,
        array    $fileData,
        string   $collection = 'default',
        array    $options = []
    ): Media
    {
        try {
            // Простая валидация как в оригинале
            if (!isset($fileData['data']) || empty($fileData['data'])) {
                throw new Exception('Отсутствуют данные файла');
            }

            // Извлечение и очистка base64 данных
            $cleanBase64Data = $this->extractBase64Data($fileData['data']);

            // Декодирование base64 как в оригинале (без strict mode)
            $binaryData = base64_decode($cleanBase64Data);
            if ($binaryData === false) {
                throw new Exception('Ошибка декодирования base64 данных');
            }

            // Валидация размера файла
            if ($this->validateSize && strlen($binaryData) > $this->maxFileSize) {
                throw new Exception('Размер файла превышает допустимый лимит: ' . $this->formatBytes($this->maxFileSize));
            }

            // Определение MIME типа
            $mimeType = $this->detectMimeType($binaryData, $fileData);

            // Валидация MIME типа
            if ($this->validateMimeType && !isset($this->allowedMimeTypes[$mimeType])) {
                throw new Exception('Недопустимый тип файла: ' . $mimeType);
            }

            // Определение расширения
            $extension = $this->allowedMimeTypes[$mimeType] ?? 'bin';

            // Генерация имени файла
            $filename = $this->generateFilename($fileData, $extension, $options);

            // Используем временный файл по умолчанию (как в оригинале)
            $media = $this->uploadViaTempFile($model, $binaryData, $filename, $fileData, $collection, $options);

            if (!$media) {
                throw new Exception('Не удалось создать медиа файл');
            }

            return $media;

        } catch (Exception $e) {
            throw $e;
        }
    }

    private function extractBase64Data(string $data): string
    {
        // Простая обработка как в оригинальном коде
        // Удаляем префикс data:mime;base64, если он есть
        if (strpos($data, 'data:') === 0) {
            if (preg_match('/^data:[^;]+;base64,(.+)$/', $data, $matches)) {
                return $matches[1];
            }
            // Если формат data: некорректный, пытаемся удалить все до запятой
            $commaPos = strpos($data, ',');
            if ($commaPos !== false) {
                return substr($data, $commaPos + 1);
            }
        }

        return $data;
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    private function detectMimeType(string $binaryData, array $fileData): string
    {
        // Сначала пытаемся определить из переданного типа (как в оригинале)
        if (isset($fileData['type']) && !empty($fileData['type'])) {
            return $fileData['type'];
        }

        // Если есть data URI, извлекаем MIME тип из него
        if (isset($fileData['data']) && strpos($fileData['data'], 'data:') === 0) {
            if (preg_match('/^data:([^;]+);base64,/', $fileData['data'], $matches)) {
                return $matches[1];
            }
        }

        // Определяем MIME тип из бинарных данных
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->buffer($binaryData);

        if ($detectedMime !== false) {
            return $detectedMime;
        }

        return 'application/octet-stream';
    }

    private function generateFilename(array $fileData, string $extension, array $options): string
    {
        // Если передано конкретное имя файла
        if (isset($fileData['name']) && !empty($fileData['name'])) {
            // Получаем переданное имя без расширения
            $baseName = pathinfo($fileData['name'], PATHINFO_FILENAME);

            // Добавляем уникальный идентификатор для избежания конфликтов
            $uniqueId = time() . '_' . Str::random(8);

            return $baseName . '_' . $uniqueId . '.' . $extension;
        }

        // Если передан префикс для имени файла
        $prefix = $options['filename_prefix'] ?? '';

        // Если передан суффикс для имени файла
        $suffix = $options['filename_suffix'] ?? '';

        // Генерируем имя файла как в оригинале: prefix + time + suffix + extension
        if (!empty($prefix)) {
            $randomName = $prefix . time() . $suffix . '.' . $extension;
        } else {
            $randomName = Str::random(20) . $suffix . '.' . $extension;
        }

        return $randomName;
    }

    private function uploadViaTempFile(
        HasMedia $model,
        string   $binaryData,
        string   $filename,
        array    $fileData,
        string   $collection,
        array    $options
    ): Media
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $tempPath = sys_get_temp_dir() . '/' . uniqid() . '.' . $extension;

        file_put_contents($tempPath, $binaryData);

        try {
            $mediaAdder = $model->addMedia($tempPath)->usingFileName($filename);

            // Устанавливаем имя если передано
            if (isset($fileData['name']) && !empty($fileData['name'])) {
                $mediaAdder->usingName($fileData['name']);
            }

            // Добавляем дополнительные метаданные
            if (isset($options['custom_properties'])) {
                $mediaAdder->withCustomProperties($options['custom_properties']);
            }

            $media = $mediaAdder->toMediaCollection($collection);

            return $media;
        } finally {
            // Удаляем временный файл
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    }

    public function deleteFilesByCollection(HasMedia $model, string $collection): void
    {
        $model->clearMediaCollection($collection);
    }

    public function deleteFileById(HasMedia $model, int $mediaId): bool
    {
        $media = $model->getMedia()->where('id', $mediaId)->first();

        if ($media) {
            $media->delete();
            return true;
        }

        return false;
    }

    public function getAllowedMimeTypes(): array
    {
        return $this->allowedMimeTypes;
    }

    public function setAllowedMimeTypes(array $mimeTypes): self
    {
        $this->allowedMimeTypes = $mimeTypes;
        return $this;
    }

    public function isAllowedMimeType(string $mimeType): bool
    {
        return isset($this->allowedMimeTypes[$mimeType]);
    }

    public function getMaxFileSize(): int
    {
        return $this->maxFileSize;
    }

    public function setMaxFileSize(int $bytes): self
    {
        $this->maxFileSize = $bytes;
        return $this;
    }

    public function getExtensionByMimeType(string $mimeType): ?string
    {
        return $this->allowedMimeTypes[$mimeType] ?? null;
    }

    public function getMimeTypesByCategory(string $category): array
    {
        $categories = [
            'images'    => ['image/'],
            'documents' => ['application/pdf', 'application/msword', 'application/vnd.openxml', 'text/'],
            'archives'  => ['application/zip', 'application/x-rar', 'application/x-7z', 'application/gzip'],
            'audio'     => ['audio/'],
            'video'     => ['video/'],
        ];

        if (!isset($categories[$category])) {
            return [];
        }

        $result = [];
        foreach ($this->allowedMimeTypes as $mimeType => $extension) {
            foreach ($categories[$category] as $prefix) {
                if (strpos($mimeType, $prefix) === 0) {
                    $result[$mimeType] = $extension;
                    break;
                }
            }
        }

        return $result;
    }

    public function resetToDefaults(): self
    {
        $this->allowedMimeTypes = [
            // Images
            'image/jpeg'                                                                => 'jpg',
            'image/png'                                                                 => 'png',
            'image/webp'                                                                => 'webp',
            'image/gif'                                                                 => 'gif',
            'image/svg+xml'                                                             => 'svg',
            'image/bmp'                                                                 => 'bmp',
            'image/tiff'                                                                => 'tiff',

            // Documents
            'application/pdf'                                                           => 'pdf',
            'application/msword'                                                        => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
            'application/vnd.ms-excel'                                                  => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
            'application/vnd.ms-powerpoint'                                             => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',

            // Text files
            'text/plain'                                                                => 'txt',
            'text/csv'                                                                  => 'csv',
            'application/json'                                                          => 'json',
            'text/xml'                                                                  => 'xml',
            'application/xml'                                                           => 'xml',

            // Archives
            'application/zip'                                                           => 'zip',
            'application/x-rar-compressed'                                              => 'rar',
            'application/x-7z-compressed'                                               => '7z',
            'application/gzip'                                                          => 'gz',

            // Audio
            'audio/mpeg'                                                                => 'mp3',
            'audio/wav'                                                                 => 'wav',
            'audio/ogg'                                                                 => 'ogg',

            // Video
            'video/mp4'                                                                 => 'mp4',
            'video/avi'                                                                 => 'avi',
            'video/quicktime'                                                           => 'mov',
            'video/webm'                                                                => 'webm',
        ];

        $this->maxFileSize = 10 * 1024 * 1024;
        $this->useTempFile = true; // По умолчанию используем временные файлы
        $this->validateSize = true;
        $this->validateMimeType = true;

        return $this;
    }

    private function validateFileData(array $fileData): void
    {
        if (!isset($fileData['data']) || empty($fileData['data'])) {
            throw new Exception('Отсутствуют данные файла');
        }
    }

    private function uploadDirectly(
        HasMedia $model,
        string   $binaryData,
        string   $filename,
        array    $fileData,
        string   $collection,
        array    $options
    ): Media
    {
        $mediaAdder = $model->addMediaFromBase64($binaryData)->usingFileName($filename);

        // Устанавливаем имя если передано
        if (isset($fileData['name']) && !empty($fileData['name'])) {
            $mediaAdder->usingName($fileData['name']);
        }

        // Добавляем дополнительные метаданные
        if (isset($options['custom_properties'])) {
            $mediaAdder->withCustomProperties($options['custom_properties']);
        }

        // Устанавливаем конверсии если переданы
        if (isset($options['conversions'])) {
            foreach ($options['conversions'] as $conversion) {
                $mediaAdder->performOnCollections($collection);
            }
        }

        return $mediaAdder->toMediaCollection($collection);
    }
}
