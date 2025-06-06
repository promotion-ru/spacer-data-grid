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
use Log;
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

            $query = User::query();

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('surname', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                });
            }

            $users = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data'    => UserResource::collection($users->items()),
                'meta'    => [
                    'current_page' => $users->currentPage(),
                    'last_page'    => $users->lastPage(),
                    'per_page'     => $users->perPage(),
                    'total'        => $users->total(),
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Users fetch error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке пользователей'
            ], 500);
        }
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('store', User::class);

        try {
            $validatedData = $request->validated();

            $user = new User();
            $user->name = $validatedData['name'];
            $user->surname = $validatedData['surname'] ?? null;
            $user->email = $validatedData['email'];
            $user->password = bcrypt($validatedData['password']);
            $user->save();

            if ($request->has('avatar')) {
                $avatar = $this->fileUploadService
                    ->onlyImages()
                    ->setMaxFileSize(2 * 1024 * 1024) // 2MB
                    ->uploadFile($user, $validatedData['avatar'], 'avatars', [
                        'filename_prefix' => $user->id . '_avatar_'
                    ]);
                $user->avatar_id = $avatar->id;
                $user->save();
            }

            $role = Role::query()->where('id', Constant::ROLE_USER)->first();
            $user->assignRole($role);

            return response()->json([
                'success' => true,
                'message' => 'Пользователь успешно создан',
                'data'    => new UserResource($user),
            ], 201);
        } catch (Exception $e) {
            Log::error('User creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании пользователя'
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
            Log::error('User fetch error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке пользователя'
            ], 500);
        }
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);
        try {
            $validatedData = $request->validated();

            $user->name = $validatedData['name'];
            $user->surname = $validatedData['surname'] ?? null;
            $user->email = $validatedData['email'];

            if ($request->filled('password')) {
                $user->password = bcrypt($validatedData['password']);
            }

            if ($request->boolean('delete_avatar')) {
                $user->clearMediaCollection('avatars');
                $user->avatar_id = null;
            } elseif ($request->has('avatar')) {
                $user->clearMediaCollection('avatars');
                $avatar = $this->fileUploadService
                    ->onlyImages()
                    ->setMaxFileSize(2 * 1024 * 1024) // 2MB
                    ->uploadFile($user, $validatedData['avatar'], 'avatars', [
                        'filename_prefix' => $user->id . '_avatar_'
                    ]);
                $user->avatar_id = $avatar->id;
            }

            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Пользователь успешно обновлен',
                'data'    => new UserResource($user),
            ]);
        } catch (Exception $e) {
            Log::error('User update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении пользователя'
            ], 500);
        }
    }

    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        try {
            $user->clearMediaCollection('avatars');
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Пользователь успешно удален',
            ]);
        } catch (Exception $e) {
            Log::error('User deletion error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении пользователя'
            ], 500);
        }
    }
}
