<?php

namespace App\Http\Controllers;

use App\Helpers\Constant;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\FileUploadService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    protected FileUploadService $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        try {
            $perPage = $request->get('per_page', 10);
            $search = $request->get('search');
            $active = $request->get('active');
            $createdFrom = $request->get('created_from');
            $createdTo = $request->get('created_to');
            $recentlyActive = $request->boolean('recently_active');
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            $query = User::query();

            // Применяем фильтры с использованием scope методов
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('surname', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                });
            }

            // Фильтр по активности
            if ($active !== null) {
                $isActive = $active === 'active' ? 1 : 0;
                $query->where('active', $isActive);
            }

            if ($createdFrom || $createdTo) {
                if ($createdFrom) {
                    $query->whereDate('created_at', '>=', $createdFrom);
                }
                if ($createdTo) {
                    $query->whereDate('created_at', '<=', $createdTo);
                }
            }

            if ($recentlyActive) {
                $query->where('updated_at', '>=', now()->subDays(7));
            }

            // Применяем сортировку
            $this->applySorting($query, $sortBy, $sortOrder);

            $users = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data'    => UserResource::collection($users->items()),
                'meta'    => [
                    'current_page' => $users->currentPage(),
                    'last_page'    => $users->lastPage(),
                    'per_page'     => $users->perPage(),
                    'total'        => $users->total(),
                    'from'         => $users->firstItem(),
                    'to'           => $users->lastItem(),
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Users fetch error: ' . $e->getMessage(), [
                'filters' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке пользователей'
            ], 500);
        }
    }

    /**
     * Применяет сортировку к запросу
     */
    private function applySorting($query, string $sortBy, string $sortOrder): void
    {
        $allowedSortFields = ['created_at', 'updated_at', 'name', 'email', 'active'];

        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }

        if (!in_array(strtolower($sortOrder), ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        $query->orderBy($sortBy, $sortOrder);

        // Добавляем дополнительную сортировку для стабильности результатов
        if ($sortBy !== 'created_at') {
            $query->orderBy('created_at', 'desc');
        }
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('store', User::class);

        try {
            DB::beginTransaction();

            $validatedData = $request->validated();

            $user = User::create([
                'name'     => $validatedData['name'],
                'surname'  => $validatedData['surname'] ?? null,
                'email'    => $validatedData['email'],
                'password' => bcrypt($validatedData['password']),
                'active'   => $validatedData['active'] ?? 1,
            ]);

            if ($request->filled('avatar')) {
                $avatar = $this->fileUploadService
                    ->onlyImages()
                    ->setMaxFileSize(2 * 1024 * 1024) // 2MB
                    ->uploadFile($user, $validatedData['avatar'], 'avatars', [
                        'filename_prefix' => $user->id . '_avatar_'
                    ]);
                $user->update(['avatar_id' => $avatar->id]);
            }

            $role = Role::query()->where('id', Constant::ROLE_USER)->first();
            if ($role) {
                $user->assignRole($role);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Пользователь успешно создан',
                'data'    => new UserResource($user),
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('User creation error: ' . $e->getMessage(), [
                'request_data' => $request->safe()->except(['password', 'avatar'])
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании пользователя'
            ], 500);
        }
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        try {
            DB::beginTransaction();

            $validatedData = $request->validated();

            $updateData = [
                'name'    => $validatedData['name'],
                'surname' => $validatedData['surname'] ?? null,
                'email'   => $validatedData['email'],
                'active'  => $validatedData['active'] ?? 1,
            ];

            if ($request->filled('password')) {
                $updateData['password'] = bcrypt($validatedData['password']);
            }

            $user->update($updateData);

            if ($request->boolean('delete_avatar')) {
                $user->clearMediaCollection('avatars');
                $user->update(['avatar_id' => null]);
            } elseif ($request->filled('avatar')) {
                $user->clearMediaCollection('avatars');
                $avatar = $this->fileUploadService
                    ->onlyImages()
                    ->setMaxFileSize(2 * 1024 * 1024) // 2MB
                    ->uploadFile($user, $validatedData['avatar'], 'avatars', [
                        'filename_prefix' => $user->id . '_avatar_'
                    ]);
                $user->update(['avatar_id' => $avatar->id]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Пользователь успешно обновлен',
                'data'    => new UserResource($user),
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('User update error: ' . $e->getMessage(), [
                'user_id'      => $user->id,
                'request_data' => $request->safe()->except(['password', 'avatar'])
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении пользователя'
            ], 500);
        }
    }

    public function show(User $user): JsonResponse
    {
        $this->authorize('show', $user);

        try {
            return response()->json([
                'success' => true,
                'data'    => new UserResource($user),
            ]);
        } catch (Exception $e) {
            Log::error('User fetch error: ' . $e->getMessage(), [
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке пользователя'
            ], 500);
        }
    }

    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        try {
            DB::beginTransaction();

            $user->clearMediaCollection('avatars');
            $user->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Пользователь успешно удален',
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('User deletion error: ' . $e->getMessage(), [
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении пользователя'
            ], 500);
        }
    }
}
