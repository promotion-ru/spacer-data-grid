<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Log;

class ProfileController extends Controller
{
    public function show(): JsonResponse
    {
        try {
            $user = Auth::user();

            return response()->json([
                'success' => true,
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
                $this->deleteUserAvatar($user);
            } elseif ($request->has('avatar')) {
                // Загружаем новый аватар из base64
                $this->uploadUserAvatarFromBase64($user, $request->avatar);
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

    /**
     * Удаляет аватар пользователя
     */
    private function deleteUserAvatar($user): void
    {
        $user->clearMediaCollection('avatars');
    }

    /**
     * Загружает аватар пользователя из base64
     */
    private function uploadUserAvatarFromBase64($user, $avatarData): void
    {
        // Удаляем старый аватар
        $this->deleteUserAvatar($user);

        // Декодируем base64
        $imageData = base64_decode($avatarData['data']);

        // Проверяем размер файла (2MB)
        if (strlen($imageData) > 2 * 1024 * 1024) {
            throw new Exception('Размер файла не должен превышать 2MB');
        }

        // Определяем расширение файла по MIME типу
        $extension = match ($avatarData['type']) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'jpg'
        };

        // Создаем временный файл
        $tempPath = sys_get_temp_dir() . '/' . uniqid() . '.' . $extension;
        file_put_contents($tempPath, $imageData);

        try {
            // Добавляем медиа файл в коллекцию
            $mediaFile = $user->addMedia($tempPath)
                ->usingName($avatarData['name'])
                ->usingFileName($user->id . '_' . time() . '.' . $extension)
                ->toMediaCollection('avatars');
            $user->avatar_id = $mediaFile->id;
        } finally {
            // Удаляем временный файл
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    }
}
