<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\TokenResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Log;

class AuthController extends Controller
{
    /**
     * Login user and create token
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Создаем токен
        $deviceName = $request->device_name ?: $this->getDeviceName($request);
        $token = $user->createToken($deviceName, ['*'], now()->addDays(30));

        // Логируем вход
        $this->logAuthActivity($request, $user, 'login', [
            'token_id'    => $token->accessToken->id,
            'device_name' => $deviceName,
        ]);

        // Загружаем роли и права для пользователя
        $user->load(['roles.permissions']);

        return response()->json([
            'message'    => 'Login successful',
            'user'       => new UserResource($user),
            'token'      => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at,
        ]);
    }

    /**
     * Generate device name from request
     */
    private function getDeviceName(Request $request): string
    {
        $userAgent = $request->userAgent();
        $ip = $request->ip();

        // Простое определение браузера
        $browser = 'Unknown';
        if (str_contains($userAgent, 'Chrome')) {
            $browser = 'Chrome';
        } elseif (str_contains($userAgent, 'Firefox')) {
            $browser = 'Firefox';
        } elseif (str_contains($userAgent, 'Safari') && !str_contains($userAgent, 'Chrome')) {
            $browser = 'Safari';
        } elseif (str_contains($userAgent, 'Edge')) {
            $browser = 'Edge';
        }

        return $browser . ' (' . $ip . ')';
    }

    /**
     * Log authentication activity
     */
    private function logAuthActivity(Request $request, User $user, string $action, array $extra = []): void
    {
        $data = array_merge([
            'action'     => $action,
            'user_id'    => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ], $extra);

        Log::info('Auth activity', $data);
    }

    /**
     * Register new user and create token
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Создаем токен после регистрации
        $deviceName = $request->device_name ?: $this->getDeviceName($request);
        $token = $user->createToken($deviceName, ['*'], now()->addDays(7));

        $this->logAuthActivity($request, $user, 'register', [
            'token_id'    => $token->accessToken->id,
            'device_name' => $deviceName,
        ]);

        // Загружаем роли и права для пользователя
        $user->load(['roles.permissions']);

        return response()->json([
            'message'    => 'Registration successful',
            'user'       => new UserResource($user),
            'token'      => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at,
        ], 201);
    }

    /**
     * Logout (revoke current token)
     */
    public function logout(Request $request): JsonResponse
    {
        $tokenId = $request->user()->currentAccessToken()->id;

        $this->logAuthActivity($request, $request->user(), 'logout', [
            'token_id' => $tokenId,
        ]);

        // Удаляем текущий токен
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successful'
        ]);
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        // Загружаем роли и права
        $user->load(['roles.permissions']);

        return response()->json(new UserResource($user));
    }

    /**
     * Revoke all tokens except current
     */
    public function logoutOtherDevices(Request $request): JsonResponse
    {
        $currentTokenId = $request->user()->currentAccessToken()->id;

        $deletedCount = $request->user()->tokens()
            ->where('id', '!=', $currentTokenId)
            ->delete();

        $this->logAuthActivity($request, $request->user(), 'logout_other_devices', [
            'current_token_id' => $currentTokenId,
            'deleted_count'    => $deletedCount,
        ]);

        return response()->json([
            'message'              => 'Other devices logged out successfully',
            'revoked_tokens_count' => $deletedCount,
        ]);
    }

    /**
     * Get user's active tokens
     */
    public function tokens(Request $request): JsonResponse
    {
        $currentTokenId = $request->user()->currentAccessToken()->id;

        $tokens = $request->user()->tokens()
            ->select('id', 'name', 'abilities', 'last_used_at', 'expires_at', 'created_at')
            ->orderBy('last_used_at', 'desc')
            ->get()
            ->map(function ($token) use ($currentTokenId) {
                $token->is_current = $token->id === $currentTokenId;
                return $token;
            });

        return response()->json(TokenResource::collection($tokens));
    }

    /**
     * Revoke all tokens (logout from all devices)
     */
    public function logoutAllDevices(Request $request): JsonResponse
    {
        $deletedCount = $request->user()->tokens()->delete();

        $this->logAuthActivity($request, $request->user(), 'logout_all_devices', [
            'deleted_count' => $deletedCount,
        ]);

        return response()->json([
            'message'              => 'Logged out from all devices successfully',
            'revoked_tokens_count' => $deletedCount,
        ]);
    }

    /**
     * Revoke specific token
     */
    public function revokeToken(Request $request, $tokenId): JsonResponse
    {
        $token = $request->user()->tokens()->find($tokenId);

        if (!$token) {
            return response()->json([
                'message' => 'Token not found'
            ], 404);
        }

        // Нельзя удалить текущий токен через этот метод
        if ($token->id === $request->user()->currentAccessToken()->id) {
            return response()->json([
                'message' => 'Cannot revoke current token. Use logout instead.'
            ], 400);
        }

        $this->logAuthActivity($request, $request->user(), 'token_revoked', [
            'revoked_token_id' => $tokenId,
            'token_name'       => $token->name,
        ]);

        $token->delete();

        return response()->json([
            'message' => 'Token revoked successfully'
        ]);
    }

    /**
     * Refresh current token (create new, revoke old)
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $user->currentAccessToken();
        $deviceName = $currentToken->name;

        // Создаем новый токен
        $newToken = $user->createToken($deviceName, ['*'], now()->addDays(7));

        // Удаляем старый токен
        $currentToken->delete();

        $this->logAuthActivity($request, $user, 'token_refreshed', [
            'old_token_id' => $currentToken->id,
            'new_token_id' => $newToken->accessToken->id,
        ]);

        return response()->json([
            'message'    => 'Token refreshed successfully',
            'token'      => $newToken->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $newToken->accessToken->expires_at,
        ]);
    }

    /**
     * Check token validity
     */
    public function checkToken(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();
        $user = $request->user();
        $user->load(['roles.permissions']);

        return response()->json([
            'valid'      => true,
            'token_info' => new TokenResource($token),
            'user'       => new UserResource($user),
        ]);
    }
}
