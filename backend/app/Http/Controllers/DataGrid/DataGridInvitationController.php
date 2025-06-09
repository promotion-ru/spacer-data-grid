<?php
// app/Http/Controllers/Api/DataGridInvitationController.php
namespace App\Http\Controllers\DataGrid;

use App\Http\Controllers\Controller;
use App\Http\Requests\InviteUserRequest;
use App\Http\Resources\DataGridInvitationResource;
use App\Models\DataGrid;
use App\Models\DataGridInvitation;
use App\Models\DataGridMember;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DataGridInvitationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $invitations = auth()->user()
            ->pendingInvitations()
            ->with(['dataGrid.user', 'invitedBy'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data'    => DataGridInvitationResource::collection($invitations),
        ]);
    }

    public function store(InviteUserRequest $request, DataGrid $dataGrid): JsonResponse
    {
        $this->authorize('share', $dataGrid);

        $email = $request->validated('email');
        $permissions = $request->validated('permissions', ['view']);

        // Проверяем, не является ли пользователь уже участником
        $user = User::query()->where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Пользователь не найден',
            ], 422);
        }

        if ($dataGrid->isMember($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Пользователь уже является участником таблицы',
            ], 422);
        }

        // Проверяем, не приглашен ли уже этот пользователь
        $existingInvitation = $dataGrid->invitations()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($existingInvitation) {
            return response()->json([
                'success' => false,
                'message' => 'Пользователь уже приглашен в эту таблицу',
            ], 422);
        }

        // Проверяем, не является ли это владельцем
        if ($dataGrid->user->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя пригласить владельца таблицы',
            ], 422);
        }

        $invitation = $dataGrid->invitations()->create([
            'invited_by'  => auth()->id(),
            'user_id'     => $user->id,
            'permissions' => $permissions,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Приглашение отправлено',
            'data'    => new DataGridInvitationResource($invitation),
        ], 201);
    }

    public function accept(Request $request, string $token): JsonResponse
    {
        $invitation = DataGridInvitation::query()
            ->where('token', $token)
            ->where('status', 'pending')
            ->first();

        if (!$invitation) {
            return response()->json([
                'success' => false,
                'message' => 'Приглашение не найдено или уже обработано',
            ], 404);
        }

        if ($invitation->user_id !== auth()->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Приглашение предназначено для другого пользователя',
            ], 403);
        }

        // Проверяем, не является ли пользователь уже участником
        if ($invitation->dataGrid->isMember(auth()->user())) {
            $invitation->update(['status' => 'accepted']);
            return response()->json([
                'success' => false,
                'message' => 'Вы уже являетесь участником этой таблицы',
            ], 422);
        }

        // Создаем участника
        DataGridMember::query()->create([
            'data_grid_id' => $invitation->data_grid_id,
            'user_id'      => auth()->id(),
            'invited_by'   => $invitation->invited_by,
            'permissions'  => $invitation->permissions,
        ]);

        $invitation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Приглашение принято',
        ]);
    }

    public function decline(Request $request, string $token): JsonResponse
    {
        $invitation = DataGridInvitation::query()
            ->where('token', $token)
            ->where('status', 'pending')
            ->first();

        if (!$invitation) {
            return response()->json([
                'success' => false,
                'message' => 'Приглашение не найдено или уже обработано',
            ], 404);
        }

        if ($invitation->user_id !== auth()->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Приглашение предназначено для другого пользователя',
            ], 403);
        }

        $invitation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Приглашение отклонено',
        ]);
    }

    public function destroy(DataGrid $dataGrid, DataGridInvitation $invitation): JsonResponse
    {
        $this->authorize('share', $dataGrid);

        $invitation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Приглашение отменено',
        ]);
    }
}
