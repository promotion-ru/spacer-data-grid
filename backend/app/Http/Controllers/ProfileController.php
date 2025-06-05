<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Services\FileUploadService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Log;

class ProfileController extends Controller
{
    protected FileUploadService $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    public function show(): JsonResponse
    {
        try {
            $user = Auth::user();

            return response()->json([
                'success' => true,
                'data'    => new ProfileResource($user),
            ]);
        } catch (Exception $e) {
            Log::error('Profile fetch error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке профиля'
            ], 500);
        }
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $validatedData = $request->validated();
            $user->name = $validatedData['name'];
            $user->surname = $validatedData['surname'] ?? null;
            $user->email = $validatedData['email'];

            if ($request->filled('password')) {
                $user->password = bcrypt($validatedData['password']);
            }

            if ($request->boolean('delete_avatar')) {
                // Удаляем существующий аватар
                $user->clearMediaCollection('avatars');
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
                'message' => 'Профиль успешно обновлен',
                'data'    => new ProfileResource($user),
            ]);

        } catch (Exception $e) {
            Log::error('Profile update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении профиля'
            ], 500);
        }
    }
}
