<?php

namespace App\Http\Controllers\DataGrid;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataGridType\SearchDataGridTypeRequest;
use App\Http\Requests\DataGridType\StoreDataGridTypeRequest;
use App\Http\Requests\DataGridType\UpdateDataGridTypeRequest;
use App\Http\Resources\DataGridType\DataGridTypeResource;
use App\Models\DataGridType;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DataGridTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DataGridType::class);

        try {
            $perPage = $request->get('per_page', 10);
            $search = $request->get('search');
            $dataGridId = $request->get('data_grid_id');
            $myTypes = $request->boolean('my_types');
            $createdFrom = $request->get('created_from');
            $createdTo = $request->get('created_to');

            $query = DataGridType::query()
                ->with(['creator', 'dataGrid'])
                ->select('data_grid_types.*')
                ->leftJoin('data_grids', 'data_grid_types.data_grid_id', '=', 'data_grids.id')
                ->leftJoin('users as creators', 'data_grid_types.created_by', '=', 'creators.id');

            // Фильтр по таблице данных
            if ($dataGridId) {
                $query->where('data_grid_types.data_grid_id', $dataGridId);
            }

            // Фильтр "мои типы"
            if ($myTypes) {
                $query->where('data_grid_types.created_by', auth()->id());
            }

            // Расширенный поиск по названию типа, таблице и создателю
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('data_grid_types.name', 'LIKE', "%{$search}%")
                        ->orWhere('data_grids.name', 'LIKE', "%{$search}%")
                        ->orWhere('creators.name', 'LIKE', "%{$search}%")
                        ->orWhere('creators.email', 'LIKE', "%{$search}%");
                });
            }

            // Фильтр по дате создания "от"
            if ($createdFrom) {
                $query->whereDate('data_grid_types.created_at', '>=', $createdFrom);
            }

            // Фильтр по дате создания "до"
            if ($createdTo) {
                $query->whereDate('data_grid_types.created_at', '<=', $createdTo);
            }

            // Сортировка с поддержкой новых полей
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            // Маппинг полей для сортировки с учетом joined таблиц
            $sortFieldMapping = [
                'created_at'     => 'data_grid_types.created_at',
                'name'           => 'data_grid_types.name',
                'data_grid_name' => 'data_grids.name',
                'creator_name'   => 'creators.name',
                'updated_at'     => 'data_grid_types.updated_at'
            ];

            $sortField = $sortFieldMapping[$sortBy] ?? 'data_grid_types.created_at';
            $query->orderBy($sortField, $sortOrder);

            // Добавляем дополнительную сортировку для стабильности результатов
            if ($sortBy !== 'created_at') {
                $query->orderBy('data_grid_types.created_at', 'desc');
            }

            $types = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data'    => DataGridTypeResource::collection($types->items()),
                'meta'    => [
                    'current_page' => $types->currentPage(),
                    'last_page'    => $types->lastPage(),
                    'per_page'     => $types->perPage(),
                    'total'        => $types->total(),
                    'from'         => $types->firstItem(),
                    'to'           => $types->lastItem(),
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Types fetch error: ' . $e->getMessage(), [
                'filters' => $request->all(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке типов'
            ], 500);
        }
    }

    public function search(SearchDataGridTypeRequest $request): JsonResponse
    {
        try {
            $query = DataGridType::query()
                ->with(['creator', 'dataGrid'])
                ->forDataGrid($request->data_grid_id);

            if ($request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%");
                });
            }

            $types = $query->limit($request->limit)->get();

            return response()->json([
                'data' => DataGridTypeResource::collection($types),
                'meta' => [
                    'total' => $types->count(),
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Ошибка поиска типов данных', [
                'error'        => $e->getMessage(),
                'data_grid_id' => $request->data_grid_id,
                'search'       => $request->search
            ]);

            return response()->json([
                'message' => 'Ошибка поиска типов',
                'error'   => app()->isLocal() ? $e->getMessage() : 'Внутренняя ошибка сервера'
            ], 500);
        }
    }

    public function store(StoreDataGridTypeRequest $request): JsonResponse
    {
        $this->authorize('create', DataGridType::class);

        try {
            DB::beginTransaction();

            $type = DataGridType::create([
                'name'         => $request->name,
                'data_grid_id' => $request->data_grid_id,
                'created_by'   => auth()->id()
            ]);

            $type->load(['creator', 'dataGrid']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Тип успешно создан',
                'data'    => new DataGridTypeResource($type)
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Type creation error: ' . $e->getMessage(), [
                'request_data' => $request->validated(),
                'user_id'      => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании типа'
            ], 500);
        }
    }

    public function show(DataGridType $dataGridType): JsonResponse
    {
        $this->authorize('view', $dataGridType);

        try {
            $dataGridType->load(['creator', 'dataGrid']);

            return response()->json([
                'success' => true,
                'data'    => new DataGridTypeResource($dataGridType)
            ]);
        } catch (Exception $e) {
            Log::error('Type fetch error: ' . $e->getMessage(), [
                'type_id' => $dataGridType->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке типа'
            ], 500);
        }
    }

    public function update(UpdateDataGridTypeRequest $request, DataGridType $dataGridType): JsonResponse
    {
        $this->authorize('update', $dataGridType);

        try {
            DB::beginTransaction();

            $dataGridType->update([
                'name'         => $request->name,
                'data_grid_id' => $request->data_grid_id,
            ]);

            $dataGridType->load(['creator', 'dataGrid']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Тип успешно обновлен',
                'data'    => new DataGridTypeResource($dataGridType)
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Type update error: ' . $e->getMessage(), [
                'type_id'      => $dataGridType->id,
                'request_data' => $request->validated(),
                'user_id'      => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении типа'
            ], 500);
        }
    }

    public function destroy(DataGridType $dataGridType): JsonResponse
    {
        $this->authorize('delete', $dataGridType);

        try {
            if ($dataGridType->dataGridRecords()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Тип нельзя удалить, так как он используется в записях'
                ], 422);
            }

            DB::beginTransaction();
            $dataGridType->delete();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Тип успешно удален'
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Type deletion error: ' . $e->getMessage(), [
                'type_id' => $dataGridType->id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении типа'
            ], 500);
        }
    }
}
