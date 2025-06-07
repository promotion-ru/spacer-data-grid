<?php

namespace App\Http\Controllers;

use App\Models\DataGridRecord;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataGridFileDownloadController extends Controller
{
    public function downloadMedia(Request $request, string $gridId, string $recordId, string $mediaId): StreamedResponse|JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Пользователь не авторизован'
                ], 401);
            }

            // Находим запись
            $record = DataGridRecord::query()
                ->where('id', $recordId)
                ->where('data_grid_id', $gridId)
                ->with('dataGrid')
                ->first();

            if (!$record) {
                return response()->json([
                    'success' => false,
                    'message' => 'Запись не найдена'
                ], 404);
            }

            // Проверяем права доступа к таблице
            if (!$this->hasGridAccess($record->dataGrid, $user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Нет доступа к таблице'
                ], 403);
            }

            // Находим медиафайл
            $media = $record->getMedia('attachments')->where('id', $mediaId)->first();
            if (!$media) {
                return response()->json([
                    'success' => false,
                    'message' => 'Файл не найден'
                ], 404);
            }

            // Получаем URL файла в S3
            $s3Url = $media->getUrl();
            $fileName = $media->name ?: $media->file_name;
            $mimeType = $media->mime_type ?: 'application/octet-stream';

            // Проверяем доступность файла
            $headers = get_headers($s3Url, 1);
            if (!$headers || strpos($headers[0], '200') === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Файл недоступен в хранилище'
                ], 404);
            }

            // Получаем размер файла из заголовков
            $fileSize = isset($headers['Content-Length']) ? $headers['Content-Length'] : null;

            return response()->stream(function () use ($s3Url) {
                $context = stream_context_create([
                    'http' => [
                        'timeout'         => 300,
                        'user_agent'      => 'Laravel MediaLibrary Downloader/1.0',
                        'follow_location' => true,
                        'max_redirects'   => 3
                    ]
                ]);

                $handle = fopen($s3Url, 'rb', false, $context);

                if (!$handle) {
                    echo 'Ошибка при открытии файла';
                    return;
                }

                while (!feof($handle)) {
                    $chunk = fread($handle, 8192);
                    if ($chunk === false) {
                        break;
                    }
                    echo $chunk;
                    flush();

                    // Проверяем, не прервал ли клиент соединение
                    if (connection_aborted()) {
                        break;
                    }
                }

                fclose($handle);
            }, 200, [
                'Content-Type'        => $mimeType,
                'Content-Disposition' => 'attachment; filename="' . addslashes($fileName) . '"',
                'Content-Length'      => $fileSize,
                'Cache-Control'       => 'no-cache, no-store, must-revalidate',
                'Pragma'              => 'no-cache',
                'Expires'             => '0',
                'Accept-Ranges'       => 'bytes'
            ]);

        } catch (Exception $e) {
            Log::error('Media download error: ' . $e->getMessage(), [
                'grid_id'   => $gridId,
                'record_id' => $recordId,
                'media_id'  => $mediaId,
                'user_id'   => $request->user()?->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при скачивании файла'
            ], 500);
        }
    }

    private function hasGridAccess($dataGrid, $user): bool
    {
        if (!$user || !$dataGrid) {
            return false;
        }

        // Владелец имеет полный доступ
        if ($dataGrid->user_id === $user->id) {
            return true;
        }

        // Проверяем права участника
        $member = $dataGrid->members()->where('user_id', $user->id)->first();
        if (!$member) {
            return false;
        }

        $permissions = $member->pivot->permissions ?? [];
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?? [];
        }

        return in_array('view', $permissions) ||
            in_array('*', $permissions) ||
            in_array('manage', $permissions);
    }
}
