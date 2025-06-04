<?php

namespace App\Http\Controllers;

use App\Facades\TelegramDump;
use App\Http\Requests\DataGridRequest;
use App\Http\Resources\DataGridResource;
use App\Models\DataGrid;
use Exception;
use Gate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DataGridController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (!Gate::allows('table.view')) {
            abort(403, 'Доступ запрещен');
        }

        $user = Auth::user();

        $dataGrids = DataGrid::query()
            ->where('user_id', $user->id)
            ->with(['media'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data'    => DataGridResource::collection($dataGrids),
        ]);
    }

    public function store(DataGridRequest $request): JsonResponse
    {
        if (!Gate::allows('table.create')) {
            abort(403, 'Доступ запрещен');
        }
        $user = Auth::user();

        $dataGrid = new DataGrid();
        $dataGrid->fill($request->validated());
        $dataGrid->user_id = $user->id;
        $dataGrid->save();

        if ($request->has('image')) {
            $mediaFile = $this->uploadImageFromBase64($dataGrid, $request->image);
            $dataGrid->image_id = $mediaFile->id;
            $dataGrid->save();
        }

        $dataGrid->load(['media']);

        return response()->json([
            'success' => true,
            'message' => 'Таблица данных успешно создана',
            'data'    => new DataGridResource($dataGrid),
        ], 201);
    }

    private function uploadImageFromBase64($dataGrid, $imageData)
    {
        $this->deleteImage($dataGrid);

        // Извлекаем чистую base64 строку из data URI
        $dataUri = $imageData['data'];

        // Проверяем, что это data URI
        if (!preg_match('/^data:image\/(\w+);base64,(.+)$/', $dataUri, $matches)) {
            throw new Exception('Неверный формат data URI');
        }

        $mimeType = $matches[1]; // jpeg, png, gif, webp
        $base64Data = $matches[2]; // чистая base64 строка

        // Декодируем base64
        $decodedImageData = base64_decode($base64Data);

        if ($decodedImageData === false) {
            throw new Exception('Ошибка декодирования base64');
        }

        // Проверяем размер файла (2MB)
        if (strlen($decodedImageData) > 2 * 1024 * 1024) {
            throw new Exception('Размер файла не должен превышать 2MB');
        }

        // Определяем расширение файла по извлеченному MIME типу
        $extension = match ($mimeType) {
            'jpeg' => 'jpg',
            'png' => 'png',
            'gif' => 'gif',
            'webp' => 'webp',
            default => 'jpg'
        };

        // Создаем временный файл
        $tempPath = sys_get_temp_dir() . '/' . uniqid() . '.' . $extension;
        file_put_contents($tempPath, $decodedImageData);

        try {
            // Добавляем медиа файл в коллекцию
            $mediaFile = $dataGrid->addMedia($tempPath)
                ->usingName($imageData['name'])
                ->usingFileName($dataGrid->id . '_' . time() . '.' . $extension)
                ->toMediaCollection('data_grid_image');
            return $mediaFile;
        } finally {
            // Удаляем временный файл
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    }

    private function deleteImage($user): void
    {
        $user->clearMediaCollection('data_grid_image');
    }

    public function show(DataGrid $dataGrid): JsonResponse
    {
        if (!Gate::allows('table.view', $dataGrid)) {
            abort(403, 'Доступ запрещен');
        }

        $dataGrid->load(['records.media', 'records.creator', 'media']);

        return response()->json([
            'success' => true,
            'data'    => new DataGridResource($dataGrid),
        ]);
    }

    public function destroy(DataGrid $dataGrid): JsonResponse
    {
        if (!Gate::allows('table.delete', $dataGrid)) {
            abort(403, 'Доступ запрещен');
        }

        $dataGrid->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Таблица данных успешно удалена',
        ]);
    }

    public function update(DataGridRequest $request, DataGrid $dataGrid): JsonResponse
    {
        if (!Gate::allows('table.update', $dataGrid)) {
            abort(403, 'Доступ запрещен');
        }

        $dataGrid->fill($request->validated());

        if ($request->hasFile('image')) {
            $dataGrid->clearMediaCollection('data_grid_image');
            $mediaFile = $dataGrid->addMediaFromRequest('image')->toMediaCollection('data_grid_image');
            $dataGrid->image_id = $mediaFile->id;
        }
        $dataGrid->save();
        $dataGrid->load(['media']);

        return response()->json([
            'success' => true,
            'message' => 'Таблица данных успешно обновлена',
            'data'    => new DataGridResource($dataGrid),
        ]);
    }
}
