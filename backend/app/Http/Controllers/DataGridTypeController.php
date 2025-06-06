<?php

namespace App\Http\Controllers;

use App\Http\Requests\DataGridType\SearchDataGridTypeRequest;
use App\Http\Requests\DataGridType\StoreDataGridTypeRequest;
use App\Http\Resources\DataGridType\DataGridTypeResource;
use App\Models\DataGridType;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DataGridTypeController extends Controller
{
    public function search(SearchDataGridTypeRequest $request): JsonResponse
    {
        try {
            $types = DataGridType::forDataGrid($request->data_grid_id)
                ->when($request->search, function ($query) use ($request) {
                    $query->where('name', 'like', "%{$request->search}%");
                })
                ->limit($request->limit)
                ->get();

            return response()->json([
                'data' => DataGridTypeResource::collection($types),
                'meta' => [
                    'total'      => $types->count(),
                    'has_global' => $types->where('is_global', true)->count() > 0,
                    'has_local'  => $types->where('is_global', false)->count() > 0
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
        try {
            DB::beginTransaction();

            $type = DataGridType::create([
                'name'         => $request->name,
                'data_grid_id' => $request->data_grid_id,
                'is_global'    => $request->is_global,
                'created_by'   => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Тип успешно создан',
                'data'    => new DataGridTypeResource($type)
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Ошибка создания типа данных', [
                'error'        => $e->getMessage(),
                'request_data' => $request->validated(),
                'user_id'      => auth()->id()
            ]);

            return response()->json([
                'message' => 'Ошибка создания типа',
                'error'   => app()->isLocal() ? $e->getMessage() : 'Не удалось создать тип'
            ], 422);
        }
    }

    public function show(DataGridType $dataGridType): JsonResponse
    {
        try {
            $dataGridType->load('creator');

            return response()->json([
                'data' => new DataGridTypeResource($dataGridType)
            ]);
        } catch (Exception $e) {
            Log::error('Ошибка получения типа данных', [
                'error'   => $e->getMessage(),
                'type_id' => $dataGridType->id
            ]);

            return response()->json([
                'message' => 'Ошибка получения типа'
            ], 500);
        }
    }

    public function destroy(DataGridType $dataGridType): JsonResponse
    {
        try {
            if ($dataGridType->is_global && !auth()->user()->can('delete-global-types')) {
                return response()->json([
                    'message' => 'Недостаточно прав для удаления глобального типа'
                ], 403);
            }

            if ($dataGridType->dataGridRecords()->exists()) {
                return response()->json([
                    'message' => 'Тип нельзя удалить, так как он используется в записях'
                ], 422);
            }

            DB::beginTransaction();
            $dataGridType->delete();
            DB::commit();

            return response()->json([
                'message' => 'Тип успешно удален'
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Ошибка удаления типа данных', [
                'error'   => $e->getMessage(),
                'type_id' => $dataGridType->id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'message' => 'Ошибка удаления типа'
            ], 500);
        }
    }
}
