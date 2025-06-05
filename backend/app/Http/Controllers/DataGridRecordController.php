<?php

namespace App\Http\Controllers;

use App\Facades\TelegramDump;
use App\Http\Requests\DataGridRecordRequest;
use App\Http\Resources\DataGridRecordResource;
use App\Models\DataGrid;
use App\Models\DataGridRecord;
use App\Models\DataGridRecordMedia;
use App\Services\FileUploadService;
use Exception;
use Gate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Log;

class DataGridRecordController extends Controller
{
    protected FileUploadService $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    public function index(Request $request, DataGrid $dataGrid): JsonResponse
    {
        if (!Gate::allows('table.view', $dataGrid)) {
            abort(403, 'Доступ запрещен');
        }

        $records = $dataGrid->records()
            ->with(['media', 'creator', 'attachments'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data'    => DataGridRecordResource::collection($records),
        ]);
    }

    public function store(DataGridRecordRequest $request, DataGrid $dataGrid): JsonResponse
    {
        if (!Gate::allows('table.create', $dataGrid)) {
            abort(403, 'Доступ запрещен');
        }

        $record = $dataGrid->records()->create([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        // Обрабатываем вложения
        if ($request->has('new_attachments')) {
            $this->processAttachments($record, $request->input('new_attachments'));
        }

        $record->load(['media', 'creator']);

        return response()->json([
            'success' => true,
            'message' => 'Запись успешно создана',
            'data'    => new DataGridRecordResource($record),
        ], 201);
    }

    private function processAttachments($record, $attachments): void
    {
        foreach ($attachments as $attachment) {
            try {
                $media = $this->fileUploadService
                    ->uploadFile($record, $attachment, 'attachments', [
                        'filename_prefix' => $record->id . '_'
                    ]);
                DataGridRecordMedia::query()->create([
                    'data_grid_record_id' => $record->id,
                    'media_id'            => $media->id,
                ]);

            } catch (Exception $e) {
                Log::error('Ошибка загрузки вложения', [
                    'attachment_name' => $attachment['name'] ?? 'не указано',
                    'error'           => $e->getMessage()
                ]);
                TelegramDump::dump($e->getMessage());
            }
        }
    }

    public function show(DataGrid $dataGrid, DataGridRecord $record): JsonResponse
    {
        if (!Gate::allows('table.view', $dataGrid)) {
            abort(403, 'Доступ запрещен');
        }

        $record->load(['media', 'creator', 'dataGrid']);

        return response()->json([
            'success' => true,
            'data'    => new DataGridRecordResource($record),
        ]);
    }

    public function update(DataGridRecordRequest $request, DataGrid $dataGrid, DataGridRecord $record): JsonResponse
    {
        if (!Gate::allows('table.update', $dataGrid)) {
            abort(403, 'Доступ запрещен');
        }

        $record->update($request->validated());

        if ($request->has('new_attachments')) {
            $this->processAttachments($record, $request->input('new_attachments'));
        }

        if ($request->has('remove_attachments')) {
            $this->removeAttachments($record, $request->input('remove_attachments'));
        }

        $record->load(['media', 'creator']);

        return response()->json([
            'success' => true,
            'message' => 'Запись успешно обновлена',
            'data'    => new DataGridRecordResource($record),
        ]);
    }

    private function removeAttachments($record, $mediaIds): void
    {
        foreach ($mediaIds as $mediaId) {
            try {
                $deleted = $this->fileUploadService->deleteFileById($record, $mediaId);
                if ($deleted) {
                    DataGridRecordMedia::query()
                        ->where('data_grid_record_id', $record->id)
                        ->where('media_id', $mediaId)
                        ->delete();
                }

            } catch (Exception $e) {
                Log::error('Ошибка удаления вложения', [
                    'media_id' => $mediaId,
                    'error'    => $e->getMessage()
                ]);
                TelegramDump::dump($e->getMessage());
            }
        }
    }

    public function destroy(DataGrid $dataGrid, DataGridRecord $record): JsonResponse
    {
        if (!Gate::allows('table.view', $dataGrid)) {
            abort(403, 'Доступ запрещен');
        }

        // Удаляем все вложения (включая файлы из S3)
        $record->clearMediaCollection('attachments');

        $record->delete();

        return response()->json([
            'success' => true,
            'message' => 'Запись успешно удалена',
        ]);
    }
}
