<?php

namespace App\Http\Controllers\DataGrid;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDataGridRequest;
use App\Http\Requests\UpdateDataGridRequest;
use App\Http\Resources\DataGridLogResource;
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

        // Начинаем с базового запроса
        $ownGridsQuery = DataGrid::query()
            ->where('user_id', $user->id)
            ->with(['media', 'records']);

        $sharedGridsQuery = $user->sharedGrids()
            ->with(['media', 'user']);

        // Применяем фильтры
        $this->applyFilters($ownGridsQuery, $request);
        $this->applyFilters($sharedGridsQuery, $request);

        // Получаем данные
        $ownGrids = $ownGridsQuery->get()->map(function ($grid) {
            $grid->is_owner = true;
            $grid->permissions = ['view', 'create', 'update', 'delete', 'manage'];
            return $grid;
        });

        $sharedGrids = $sharedGridsQuery->get()->map(function ($grid) {
            $grid->is_owner = false;
            $grid->permissions = json_decode($grid->pivot->permissions);
            $grid->owner_name = $grid->user->name;
            return $grid;
        });

        // Объединяем таблицы в зависимости от фильтра владения
        $ownership = $request->get('ownership');
        if ($ownership === 'own') {
            $allGrids = $ownGrids;
        } elseif ($ownership === 'shared') {
            $allGrids = $sharedGrids;
        } else {
            $allGrids = $ownGrids->concat($sharedGrids);
        }

        // Применяем сортировку
        $allGrids = $this->applySorting($allGrids, $request);

        return response()->json([
            'success' => true,
            'data'    => DataGridResource::collection($allGrids),
            'meta'    => [
                'total' => $allGrids->count(),
                'filters_applied' => $this->getAppliedFilters($request),
            ],
        ]);
    }

    private function applyFilters($query, Request $request)
    {
        // Поиск по названию и описанию
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // Фильтр по активности
        if ($request->filled('activity')) {
            $activity = $request->get('activity');
            if ($activity === 'active') {
                $query->where('is_active', true);
            } elseif ($activity === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Фильтр по дате создания
        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->get('created_from'));
        }

        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->get('created_to'));
        }

        // Базовая сортировка на уровне запроса (если не переопределена позже)
        $sort = $request->get('sort', 'created_desc');
        switch ($sort) {
            case 'created_asc':
                $query->orderBy('created_at', 'asc');
                break;
            case 'updated_desc':
                $query->orderBy('updated_at', 'desc');
                break;
            case 'updated_asc':
                $query->orderBy('updated_at', 'asc');
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'records_desc':
                $query->orderBy('records_count', 'desc');
                break;
            case 'records_asc':
                $query->orderBy('records_count', 'asc');
                break;
            case 'created_desc':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }
    }

    private function applySorting($collection, Request $request)
    {
        $sort = $request->get('sort', 'created_desc');

        switch ($sort) {
            case 'created_asc':
                return $collection->sortBy('created_at')->values();
            case 'updated_desc':
                return $collection->sortByDesc('updated_at')->values();
            case 'updated_asc':
                return $collection->sortBy('updated_at')->values();
            case 'name_asc':
                return $collection->sortBy('name')->values();
            case 'name_desc':
                return $collection->sortByDesc('name')->values();
            case 'records_desc':
                return $collection->sortByDesc('records_count')->values();
            case 'records_asc':
                return $collection->sortBy('records_count')->values();
            case 'created_desc':
            default:
                return $collection->sortByDesc('created_at')->values();
        }
    }

    private function getAppliedFilters(Request $request): array
    {
        $appliedFilters = [];

        if ($request->filled('search')) {
            $appliedFilters['search'] = $request->get('search');
        }

        if ($request->filled('ownership')) {
            $appliedFilters['ownership'] = $request->get('ownership');
        }

        if ($request->filled('activity')) {
            $appliedFilters['activity'] = $request->get('activity');
        }

        if ($request->filled('sort')) {
            $appliedFilters['sort'] = $request->get('sort');
        }

        if ($request->filled('created_from')) {
            $appliedFilters['created_from'] = $request->get('created_from');
        }

        if ($request->filled('created_to')) {
            $appliedFilters['created_to'] = $request->get('created_to');
        }

        return $appliedFilters;
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

        // Логирование создания
        $dataGrid->logAction(
            'created',
            'Таблица данных создана',
            null,
            [],
            [],
            $dataGrid->only(['name', 'description', 'is_active'])
        );

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
        $user = Auth::user();

        $dataGrid->load(['records.attachments', 'records.creator', 'media']);
        // Добавляем информацию о правах пользователя
        $dataGrid->is_owner = $dataGrid->isOwner($user);
        $dataGrid->permissions = $dataGrid->getUserPermissions($user);

        if (!$dataGrid->is_owner) {
            $dataGrid->owner_name = $dataGrid->user->name;
        }

        return response()->json([
            'success' => true,
            'data'    => new DataGridResource($dataGrid),
        ]);
    }

    public function destroy(DataGrid $dataGrid): JsonResponse
    {
        $this->authorize('delete', $dataGrid);

        $oldValues = $dataGrid->only(['name', 'description', 'is_active']);

        $dataGrid->update([
            'is_active' => false,
        ]);

        // Логирование удаления
        $dataGrid->logAction(
            'deleted',
            'Таблица данных деактивирована',
            null,
            [],
            $oldValues,
            ['is_active' => false]
        );

        return response()->json([
            'success' => true,
            'message' => 'Таблица данных успешно удалена',
        ]);
    }

    public function update(UpdateDataGridRequest $request, DataGrid $dataGrid): JsonResponse
    {
        $this->authorize('update', $dataGrid);

        $oldValues = $dataGrid->only(['name', 'description', 'is_active', 'image_id']);

        $dataGrid->fill($request->validated());

        if ($request->boolean('delete_image')) {
            $this->fileUploadService->deleteFilesByCollection($dataGrid, 'data_grid_image');
            $dataGrid->image_id = null;
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

        // Логирование обновления
        $newValues = $dataGrid->only(['name', 'description', 'is_active', 'image_id']);
        $changes = [];

        foreach ($newValues as $key => $newValue) {
            if ($oldValues[$key] !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValues[$key],
                    'new' => $newValue
                ];
            }
        }

        if (!empty($changes)) {
            $dataGrid->logAction(
                'updated',
                'Таблица данных обновлена',
                null,
                $oldValues,
                $newValues,
                ['changed_fields' => array_keys($changes)]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Таблица данных успешно обновлена',
            'data'    => new DataGridResource($dataGrid),
        ]);
    }

    public function logs(Request $request, DataGrid $dataGrid): JsonResponse
    {
        $this->authorize('viewLogs', $dataGrid);

        $query = $dataGrid->logs()
            ->with(['user', 'target_user'])
            ->orderBy('created_at', 'desc');

        // Фильтр по действию
        if ($request->filled('action')) {
            $query->where('action', $request->get('action'));
        }

        // Фильтр по измененным полям - ИСПРАВЛЕНО
        if ($request->filled('changed_field')) {
            $fieldName = $request->get('changed_field');
            $query->where(function($q) use ($fieldName) {
                // Проверяем в metadata->changed_fields
                $q->whereJsonContains('metadata->changed_fields', $fieldName)
                    // Или проверяем наличие поля в old_values
                    ->orWhereRaw("JSON_EXTRACT(old_values, ?) IS NOT NULL", ["$.\"{$fieldName}\""])
                    // Или проверяем наличие поля в new_values
                    ->orWhereRaw("JSON_EXTRACT(new_values, ?) IS NOT NULL", ["$.\"{$fieldName}\""]);
            });
        }

        // Поиск по тексту
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('description', 'LIKE', "%{$search}%")
                    ->orWhereHas('user', function($userQuery) use ($search) {
                        $userQuery->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('email', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('target_user', function($targetQuery) use ($search) {
                        $targetQuery->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('email', 'LIKE', "%{$search}%");
                    });
            });
        }

        // Фильтр по типу действия (группировка действий)
        if ($request->filled('action_type')) {
            $actionType = $request->get('action_type');
            $actionGroups = [
                'grid' => ['created', 'updated', 'deleted'],
                'members' => ['member_added', 'member_removed', 'member_updated', 'member_left'],
                'invitations' => ['invitation_sent', 'invitation_accepted', 'invitation_declined', 'invitation_cancelled'],
            ];

            if (isset($actionGroups[$actionType])) {
                $query->whereIn('action', $actionGroups[$actionType]);
            }
        }

        // Фильтр по пользователю (кто выполнил действие)
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->get('user_id'));
        }

        // Фильтр по целевому пользователю
        if ($request->filled('target_user_id')) {
            $query->where('target_user_id', $request->get('target_user_id'));
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
        $availableFields = $this->getAvailableChangedFields($dataGrid);

        // Получаем доступных пользователей для фильтрации

        return response()->json([
            'success' => true,
            'data' => DataGridLogResource::collection($logs->items()),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'from' => $logs->firstItem(),
                'to' => $logs->lastItem(),
            ],
            'filters' => [
                'available_fields' => $availableFields,
                'available_users' => [],
                'action_types' => [
                    ['value' => 'grid', 'label' => 'Операции с таблицей'],
                    ['value' => 'members', 'label' => 'Участники'],
                    ['value' => 'invitations', 'label' => 'Приглашения'],
                ],
            ],
        ]);
    }

    private function getAvailableChangedFields(DataGrid $dataGrid): array
    {
        // Получаем поля из metadata->changed_fields
        $metadataFields = $dataGrid->logs()
            ->whereNotNull('metadata')
            ->whereJsonLength('metadata->changed_fields', '>', 0) // Исправлено: проверяем длину массива
            ->get()
            ->pluck('metadata')
            ->filter()
            ->map(function($metadata) {
                return $metadata['changed_fields'] ?? [];
            })
            ->flatten()
            ->unique()
            ->filter() // убираем пустые значения
            ->values()
            ->toArray();

        // Также получаем поля из old_values и new_values
        $oldNewFields = $dataGrid->logs()
            ->where(function($q) {
                $q->whereNotNull('old_values')->orWhereNotNull('new_values');
            })
            ->get()
            ->map(function($log) {
                $fields = [];
                if ($log->old_values && is_array($log->old_values)) {
                    $fields = array_merge($fields, array_keys($log->old_values));
                }
                if ($log->new_values && is_array($log->new_values)) {
                    $fields = array_merge($fields, array_keys($log->new_values));
                }
                return $fields;
            })
            ->flatten()
            ->unique()
            ->filter() // убираем пустые значения
            ->values()
            ->toArray();

        $allFields = array_unique(array_merge($metadataFields, $oldNewFields));

        // Преобразуем в формат для дропдауна с читаемыми названиями
        $fieldLabels = [
            'name' => 'Название',
            'description' => 'Описание',
            'is_active' => 'Активность',
            'image_id' => 'Изображение',
            'permissions' => 'Права доступа',
            'email' => 'Email',
            'status' => 'Статус',
        ];

        return collect($allFields)
            ->filter() // убираем пустые значения
            ->map(function($field) use ($fieldLabels) {
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
