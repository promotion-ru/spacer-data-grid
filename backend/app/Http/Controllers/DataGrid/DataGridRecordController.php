<?php

namespace App\Http\Controllers\DataGrid;

use App\Facades\TelegramDump;
use App\Http\Controllers\Controller;
use App\Http\Requests\DataGridRecordRequest;
use App\Http\Resources\DataGridRecordLogResource;
use App\Http\Resources\DataGridRecordResource;
use App\Models\DataGrid;
use App\Models\DataGridRecord;
use App\Models\DataGridRecordMedia;
use App\Services\FileUploadService;
use Exception;
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
        $this->authorize('viewAny', [DataGridRecord::class, $dataGrid]);

        try {
            $perPage = $request->get('per_page', 20);
            $page = $request->get('page', 1);

            // Получаем фильтры
            $search = $request->get('search');
            $owner = $request->get('owner');
            $operationTypeId = $request->get('operation_type_id');
            $typeId = $request->get('type_id');
            $withAttachments = $request->get('with_attachments');
            $createdFrom = $request->get('created_from');
            $createdTo = $request->get('created_to');
            $operationDateFrom = $request->get('operation_date_from');
            $operationDateTo = $request->get('operation_date_to');
            $amountFrom = $request->get('amount_from');
            $amountTo = $request->get('amount_to');
            $currentUserId = $request->get('current_user_id');

            // Сортировка
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            $query = $dataGrid->records()->with(['media', 'creator', 'attachments', 'type']);

            // Применяем фильтры
            $this->applyFilters($query, [
                'search'              => $search,
                'owner'               => $owner,
                'operation_type_id'   => $operationTypeId,
                'type_id'             => $typeId,
                'with_attachments'    => $withAttachments,
                'created_from'        => $createdFrom,
                'created_to'          => $createdTo,
                'operation_date_from' => $operationDateFrom,
                'operation_date_to'   => $operationDateTo,
                'amount_from'         => $amountFrom,
                'amount_to'           => $amountTo,
                'current_user_id'     => $currentUserId,
            ]);

            // Применяем сортировку
            $this->applySorting($query, $sortBy, $sortOrder);

            // Пагинация
            $records = $query->paginate($perPage, ['*'], 'page', $page);

            // Получаем дополнительные данные для фильтров
            $recordTypes = $this->getRecordTypes($dataGrid);

            return response()->json([
                'success'         => true,
                'data'            => DataGridRecordResource::collection($records->items()),
                'pagination'      => [
                    'current_page' => $records->currentPage(),
                    'last_page'    => $records->lastPage(),
                    'per_page'     => $records->perPage(),
                    'total'        => $records->total(),
                    'from'         => $records->firstItem(),
                    'to'           => $records->lastItem(),
                ],
                'record_types'    => $recordTypes,
            ]);
        } catch (Exception $e) {
            Log::error('DataGrid records fetch error: ' . $e->getMessage(), [
                'grid_id' => $dataGrid->id,
                'filters' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке записей'
            ], 500);
        }
    }

    /**
     * Применяет фильтры к запросу
     */
    private function applyFilters($query, array $filters): void
    {
        // Поиск по названию, описанию, автору
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Фильтр по владельцу (мои/не мои записи)
        if (!empty($filters['owner']) && !empty($filters['current_user_id'])) {
            $query->byOwner($filters['owner'], $filters['current_user_id']);
        }

        // Фильтр по типу операции
        if (!empty($filters['operation_type_id'])) {
            $query->byOperationType($filters['operation_type_id']);
        }

        // Фильтр по типу записи
        if (!empty($filters['type_id'])) {
            $query->byRecordType($filters['type_id']);
        }

        // Фильтр по наличию вложений
        if (!empty($filters['with_attachments'])) {
            $query->withAttachments($filters['with_attachments']);
        }

        // Фильтр по дате создания - только если оба значения заполнены
        if (!empty($filters['created_from']) && !empty($filters['created_to'])) {
            $query->createdBetween($filters['created_from'], $filters['created_to']);
        }

        // Фильтр по дате операции - только если оба значения заполнены
        if (!empty($filters['operation_date_from']) && !empty($filters['operation_date_to'])) {
            $query->operationDateBetween($filters['operation_date_from'], $filters['operation_date_to']);
        }

        // Фильтр по сумме
        if (!empty($filters['amount_from']) && !empty($filters['amount_to'])) {
            $query->amountBetween($filters['amount_from'], $filters['amount_to']);
        } elseif (!empty($filters['amount_from'])) {
            $query->amountFrom($filters['amount_from']);
        } elseif (!empty($filters['amount_to'])) {
            $query->amountTo($filters['amount_to']);
        }
    }

    /**
     * Применяет сортировку к запросу
     */
    private function applySorting($query, string $sortBy, string $sortOrder): void
    {
        $allowedSortFields = [
            'created_at', 'updated_at', 'name', 'date', 'amount', 'operation_type_id'
        ];

        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }

        if (!in_array(strtolower($sortOrder), ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        // Специальная обработка для сортировки по автору
        if ($sortBy === 'creator') {
            $query->join('users', 'data_grid_records.created_by', '=', 'users.id')
                ->orderBy('users.name', $sortOrder)
                ->select('data_grid_records.*'); // Чтобы избежать конфликтов полей
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Добавляем дополнительную сортировку для стабильности результатов
        if ($sortBy !== 'created_at') {
            $query->orderBy('created_at', 'desc');
        }
    }

    /**
     * Получает типы записей для конкретного грида
     */
    private function getRecordTypes(DataGrid $dataGrid): array
    {
        return $dataGrid->types()->get(['id', 'name'])->toArray();
    }

    public function store(DataGridRecordRequest $request, DataGrid $dataGrid): JsonResponse
    {
        $this->authorize('create', [DataGridRecord::class, $dataGrid]);

        $record = $dataGrid->records()->create([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        // Логирование создания записи
        $dataGrid->logRecordAction(
            'record_created',
            'Создана новая запись в таблице',
            $record->id,
            [],
            $record->only(['name', 'date', 'operation_type_id', 'type_id', 'description', 'amount']),
            [
                'record_name'    => $record->name ?? 'Без названия',
                'operation_type' => $record->operation_type_id === 1 ? 'Доход' : 'Расход',
                'amount'         => $record->amount
            ]
        );

        // Обрабатываем вложения
        if ($request->has('new_attachments')) {
            $this->processAttachments($record, $request->input('new_attachments'), $dataGrid);
        }

        $record->load(['media', 'creator', 'type', 'attachments']);

        return response()->json([
            'success' => true,
            'message' => 'Запись успешно создана',
            'data'    => new DataGridRecordResource($record),
        ], 201);
    }

    private function processAttachments($record, $attachments, DataGrid $dataGrid): void
    {
        $attachedFiles = [];

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

                $attachedFiles[] = $media->name ?? $attachment['name'] ?? 'файл';

            } catch (Exception $e) {
                Log::error('Ошибка загрузки вложения', [
                    'attachment_name' => $attachment['name'] ?? 'не указано',
                    'error'           => $e->getMessage()
                ]);
                TelegramDump::dump($e->getMessage());
            }
        }

        // Логирование добавления вложений
        if (!empty($attachedFiles)) {
            $dataGrid->logRecordAction(
                'attachment_added',
                'Добавлены вложения к записи',
                $record->id,
                [],
                ['attachments' => $attachedFiles],
                [
                    'record_name' => $record->name ?? 'Без названия',
                    'files_count' => count($attachedFiles),
                    'files_names' => $attachedFiles
                ]
            );
        }
    }

    public function show(DataGrid $dataGrid, DataGridRecord $record): JsonResponse
    {
        if ($record->data_grid_id !== $dataGrid->id) {
            return response()->json([
                'success' => false,
                'message' => 'Запись не найдена в указанной таблице',
            ], 404);
        }

        $this->authorize('view', $record);

        $record->load(['media', 'creator', 'dataGrid', 'type', 'attachments']);

        return response()->json([
            'success' => true,
            'data'    => new DataGridRecordResource($record),
        ]);
    }

    public function update(DataGridRecordRequest $request, DataGrid $dataGrid, DataGridRecord $record): JsonResponse
    {
        if ($record->data_grid_id !== $dataGrid->id) {
            return response()->json([
                'success' => false,
                'message' => 'Запись не найдена в указанной таблице',
            ], 404);
        }

        $this->authorize('update', $record);

        $oldValues = $record->only(['name', 'date', 'operation_type_id', 'type_id', 'description', 'amount']);
        $record->update($request->validated());
        $newValues = $record->only(['name', 'date', 'operation_type_id', 'type_id', 'description', 'amount']);

        // Проверяем, были ли изменения
        $changes = [];
        foreach ($newValues as $key => $newValue) {
            if ($oldValues[$key] !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValues[$key],
                    'new' => $newValue
                ];
            }
        }

        // Логирование обновления записи
        if (!empty($changes)) {
            $dataGrid->logRecordAction(
                'record_updated',
                'Обновлена запись в таблице',
                $record->id,
                $oldValues,
                $newValues,
                [
                    'record_name'    => $record->name ?? 'Без названия',
                    'changed_fields' => array_keys($changes),
                    'operation_type' => $record->operation_type_id === 1 ? 'Доход' : 'Расход',
                    'amount'         => $record->amount
                ]
            );
        }

        $record->load(['media', 'creator', 'dataGrid', 'type', 'attachments']);

        if ($request->has('new_attachments')) {
            $this->processAttachments($record, $request->input('new_attachments'), $dataGrid);
        }

        if ($request->has('remove_attachments')) {
            $this->removeAttachments($record, $request->input('remove_attachments'), $dataGrid);
        }

        $record->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Запись успешно обновлена',
            'data'    => new DataGridRecordResource($record),
        ]);
    }

    private function removeAttachments($record, $mediaIds, DataGrid $dataGrid): void
    {
        $removedFiles = [];

        foreach ($mediaIds as $mediaId) {
            try {
                $mediaFile = $record->media()->where('id', $mediaId)->first();
                $fileName = $mediaFile?->name ?? 'файл';

                $deleted = $this->fileUploadService->deleteFileById($record, $mediaId);
                if ($deleted) {
                    DataGridRecordMedia::query()
                        ->where('data_grid_record_id', $record->id)
                        ->where('media_id', $mediaId)
                        ->delete();

                    $removedFiles[] = $fileName;
                }

            } catch (Exception $e) {
                Log::error('Ошибка удаления вложения', [
                    'media_id' => $mediaId,
                    'error'    => $e->getMessage()
                ]);
                TelegramDump::dump($e->getMessage());
            }
        }

        // Логирование удаления вложений
        if (!empty($removedFiles)) {
            $dataGrid->logRecordAction(
                'attachment_removed',
                'Удалены вложения из записи',
                $record->id,
                ['attachments' => $removedFiles],
                [],
                [
                    'record_name' => $record->name ?? 'Без названия',
                    'files_count' => count($removedFiles),
                    'files_names' => $removedFiles
                ]
            );
        }
    }

    public function destroy(DataGrid $dataGrid, DataGridRecord $record): JsonResponse
    {
        if ($record->data_grid_id !== $dataGrid->id) {
            return response()->json([
                'success' => false,
                'message' => 'Запись не найдена в указанной таблице',
            ], 404);
        }

        $this->authorize('delete', $record);

        $recordData = $record->only(['name', 'date', 'operation_type_id', 'type_id', 'description', 'amount']);
        $recordName = $record->name ?? 'Без названия';
        $recordId = $record->id;

        // Удаляем все вложения
        $record->clearMediaCollection('attachments');

        // Удаляем связи с медиафайлами
        DataGridRecordMedia::query()->where('data_grid_record_id', $record->id)->delete();

        $record->delete();

        // Логирование удаления записи
        $dataGrid->logRecordAction(
            'record_deleted',
            'Удалена запись из таблицы',
            $recordId,
            $recordData,
            [],
            [
                'record_name' => $recordName
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Запись успешно удалена',
        ]);
    }

    public function logs(Request $request, DataGrid $dataGrid, DataGridRecord $record): JsonResponse
    {
        $this->authorize('view', $record);

        if ($record->data_grid_id !== $dataGrid->id) {
            return response()->json([
                'success' => false,
                'message' => 'Запись не найдена в указанной таблице',
            ], 404);
        }

        $query = $dataGrid->recordLogs()
            ->where('data_grid_record_id', $record->id)
            ->with('user')
            ->orderBy('created_at', 'desc');

        // Фильтр по действию
        if ($request->filled('action')) {
            $query->where('action', $request->get('action'));
        }

        // Фильтр по типу действий
        if ($request->filled('action_type')) {
            $actionType = $request->get('action_type');
            $actionGroups = [
                'record_changes' => ['record_created', 'record_updated', 'record_deleted'],
                'attachments'    => ['attachment_added', 'attachment_removed'],
                'types'          => ['type_created', 'type_deleted'],
            ];

            if (isset($actionGroups[$actionType])) {
                $query->whereIn('action', $actionGroups[$actionType]);
            }
        }

        // Фильтр по измененным полям
        if ($request->filled('changed_field')) {
            $fieldName = $request->get('changed_field');
            $query->where(function ($q) use ($fieldName) {
                $q->whereJsonContains('metadata->changed_fields', $fieldName)
                    ->orWhereRaw("JSON_EXTRACT(old_values, ?) IS NOT NULL", ["$.\"{$fieldName}\""])
                    ->orWhereRaw("JSON_EXTRACT(new_values, ?) IS NOT NULL", ["$.\"{$fieldName}\""]);
            });
        }

        // Фильтр по пользователю
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->get('user_id'));
        }

        // Поиск по тексту
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('description', 'LIKE', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('email', 'LIKE', "%{$search}%");
                    });
            });
        }

        // Фильтр по дате
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        // Пагинация
        $perPage = $request->get('per_page', 50);
        $logs = $query->paginate($perPage);

        // Получаем доступные поля для фильтрации
        $availableFields = $this->getAvailableChangedFieldsForRecord($dataGrid, $record);

        return response()->json([
            'success'         => true,
            'data'            => DataGridRecordLogResource::collection($logs->items()),
            'pagination'      => [
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
                'per_page'     => $logs->perPage(),
                'total'        => $logs->total(),
            ],
            'filters'         => [
                'actions'          => [
                    ['value' => 'record_created', 'label' => 'Запись создана'],
                    ['value' => 'record_updated', 'label' => 'Запись обновлена'],
                    ['value' => 'record_deleted', 'label' => 'Запись удалена'],
                    ['value' => 'attachment_added', 'label' => 'Вложение добавлено'],
                    ['value' => 'attachment_removed', 'label' => 'Вложение удалено'],
                    ['value' => 'type_created', 'label' => 'Тип создан'],
                    ['value' => 'type_deleted', 'label' => 'Тип удален'],
                ],
                'action_types'     => [
                    ['value' => 'record_changes', 'label' => 'Изменения записи'],
                    ['value' => 'attachments', 'label' => 'Работа с вложениями'],
                ],
                'available_fields' => $availableFields,
            ],
            'current_user_id' => auth()->id(),
        ]);
    }

    private function getAvailableChangedFieldsForRecord(DataGrid $dataGrid, DataGridRecord $record): array
    {
        $logs = $dataGrid->recordLogs()
            ->where('data_grid_record_id', $record->id)
            ->where(function ($q) {
                $q->whereNotNull('old_values')->orWhereNotNull('new_values');
            })
            ->get();

        $allFields = collect();

        foreach ($logs as $log) {
            // Из metadata
            if ($log->metadata && isset($log->metadata['changed_fields']) && is_array($log->metadata['changed_fields'])) {
                $allFields = $allFields->merge($log->metadata['changed_fields']);
            }

            // Из old_values
            if ($log->old_values && is_array($log->old_values)) {
                $allFields = $allFields->merge(array_keys($log->old_values));
            }

            // Из new_values
            if ($log->new_values && is_array($log->new_values)) {
                $allFields = $allFields->merge(array_keys($log->new_values));
            }
        }

        $allFields = $allFields->unique()->filter()->values()->toArray();

        $fieldLabels = [
            'name'              => 'Название',
            'date'              => 'Дата',
            'operation_type_id' => 'Тип операции',
            'type_id'           => 'Тип записи',
            'amount'            => 'Сумма',
            'description'       => 'Описание',
            'attachments'       => 'Вложения',
        ];

        return collect($allFields)
            ->map(function ($field) use ($fieldLabels) {
                return [
                    'value' => $field,
                    'label' => $fieldLabels[$field] ?? $field,
                ];
            })
            ->sortBy('label')
            ->values()
            ->toArray();
    }
}
