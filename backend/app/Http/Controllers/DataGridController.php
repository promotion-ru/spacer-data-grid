<?php

namespace App\Http\Controllers;

use App\Http\Requests\DataGridRequest;
use App\Http\Requests\StoreDataGridRequest;
use App\Http\Requests\UpdateDataGridRequest;
use App\Http\Resources\DataGridResource;
use App\Models\DataGrid;
use App\Services\FileUploadService;
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
        $this->authorize('viewAny', DataGrid::class);

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

    public function store(StoreDataGridRequest $request): JsonResponse
    {
        $this->authorize('create', DataGrid::class);

        $user = Auth::user();

        $dataGrid = new DataGrid();
        $dataGrid->fill($request->validated());
        $dataGrid->user_id = $user->id;
        $dataGrid->save();

        if ($request->filled('image')) {
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
        $this->authorize('view', $dataGrid);

        $dataGrid->load(['records.attachments', 'records.creator', 'media']);

        return response()->json([
            'success' => true,
            'data'    => new DataGridResource($dataGrid),
        ]);
    }

    public function destroy(DataGrid $dataGrid): JsonResponse
    {
        $this->authorize('delete', $dataGrid);
        $dataGrid->load(['records.attachments']);

        foreach ($dataGrid->records as $record) {
            $this->fileUploadService->deleteFilesByCollection($record, 'attachments');
        }

        $dataGrid->delete();

        return response()->json([
            'success' => true,
            'message' => 'Таблица данных успешно удалена',
        ]);
    }

    public function update(UpdateDataGridRequest $request, DataGrid $dataGrid): JsonResponse
    {
        $this->authorize('update', $dataGrid);

        $dataGrid->fill($request->validated());

        if ($request->boolean('delete_image')) {
            $this->fileUploadService->deleteFilesByCollection($dataGrid, 'data_grid_image');
        } elseif ($request->has('new_image')) {
            // Удаляем старое изображение
            $this->fileUploadService->deleteFilesByCollection($dataGrid, 'data_grid_image');
            // Загружаем новое изображение
            $mediaFile = $this->fileUploadService
                ->onlyImages()                    // Только изображения
                ->setMaxFileSize(2 * 1024 * 1024) // 2MB как у вас
                ->uploadFile($dataGrid, $request->new_image, 'data_grid_image', [
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
