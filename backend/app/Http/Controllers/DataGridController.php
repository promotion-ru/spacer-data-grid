<?php

namespace App\Http\Controllers;

use App\Http\Requests\DataGridRequest;
use App\Http\Resources\DataGridResource;
use App\Models\DataGrid;
use App\Services\FileUploadService;
use Gate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DataGridController extends Controller
{
    protected FileUploadService $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

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
            // Удаляем старое изображение
            $this->fileUploadService->deleteFilesByCollection($dataGrid, 'data_grid_image');
            // Загружаем новое изображение
            $mediaFile = $this->fileUploadService
                ->onlyImages()                    // Только изображения
                ->setMaxFileSize(2 * 1024 * 1024) // 2MB как у вас
                ->uploadFile($dataGrid, $request->image, 'data_grid_image', [
                    'filename_prefix' => $dataGrid->id . '_'
                ]);
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

        if ($request->has('image')) {
            // Удаляем старое изображение
            $this->fileUploadService->deleteFilesByCollection($dataGrid, 'data_grid_image');
            // Загружаем новое изображение
            $mediaFile = $this->fileUploadService
                ->onlyImages()                    // Только изображения
                ->setMaxFileSize(2 * 1024 * 1024) // 2MB как у вас
                ->uploadFile($dataGrid, $request->image, 'data_grid_image', [
                    'filename_prefix' => $dataGrid->id . '_'
                ]);
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
