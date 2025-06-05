<?php

namespace App\Http\Controllers;

use App\Facades\TelegramDump;
use App\Services\FileUploadService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
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
                'data'    => [
                    'id'         => $user->id,
                    'name'       => $user->name,
                    'surname'    => $user->surname,
                    'email'      => $user->email,
                    'avatar_url' => $user->getFirstMediaUrl('avatars'),
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Profile fetch error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке профиля'
            ], 500);
        }
    }

    public function update(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'name'          => 'required|string|max:255',
                'surname'       => 'nullable|string|max:255',
                'email'         => 'required|email|unique:users,email,' . $user->id,
                'password'      => ['nullable', 'confirmed', Password::min(8)],
                'avatar'        => 'nullable|array',
                'avatar.data'   => 'required_with:avatar|string',
                'avatar.name'   => 'required_with:avatar|string',
                'avatar.type'   => 'required_with:avatar|string|in:image/jpeg,image/png,image/gif,image/webp',
                'delete_avatar' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка валидации',
                    'errors'  => $validator->errors()
                ], 422);
            }

            // Обновляем основные данные
            $user->name = $request->name;
            $user->surname = $request->surname;
            $user->email = $request->email;

            // Обновляем пароль, если указан
            if ($request->filled('password')) {
                $user->password = bcrypt($request->password);
            }

            // Обрабатываем аватар
            if ($request->boolean('delete_avatar')) {
                // Удаляем существующий аватар
                $user->clearMediaCollection('avatars');
            } elseif ($request->has('avatar')) {
                $user->clearMediaCollection('avatars');
                $avatar = $this->fileUploadService
                    ->onlyImages()
                    ->setMaxFileSize(2 * 1024 * 1024) // 2MB
                    ->uploadFile($user, $request->avatar, 'avatars', [
                        'filename_prefix' => $user->id . '_avatar_'
                    ]);
                $user->avatar_id = $avatar->id;
            }

            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Профиль успешно обновлен',
                'data'    => [
                    'id'               => $user->id,
                    'name'             => $user->name,
                    'surname'          => $user->surname,
                    'email'            => $user->email,
                    'avatar_url'       => $user->getFirstMediaUrl('avatars'),
                    'avatar_thumb_url' => $user->getFirstMediaUrl('avatars', 'thumb'),
                    'created_at'       => $user->created_at,
                    'updated_at'       => $user->updated_at
                ]
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
