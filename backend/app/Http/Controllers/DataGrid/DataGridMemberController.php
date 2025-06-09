<?php

namespace App\Http\Controllers\DataGrid;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMemberRequest;
use App\Http\Resources\DataGridMemberResource;
use App\Models\DataGrid;
use App\Models\DataGridMember;
use Illuminate\Http\JsonResponse;

class DataGridMemberController extends Controller
{
    public function index(DataGrid $dataGrid): JsonResponse
    {
        $this->authorize('manage', $dataGrid);

        $members = $dataGrid->members()
            ->with(['user', 'invitedBy'])
            ->latest()
            ->get();

        $invitations = $dataGrid->pendingInvitations()
            ->with(['invitedBy'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data'    => [
                'members'             => DataGridMemberResource::collection($members),
                'pending_invitations' => $invitations->map(function ($invitation) {
                    return [
                        'id'          => $invitation->id,
                        'email'       => $invitation->email,
                        'permissions' => $invitation->permissions,
                        'invited_by'  => $invitation->invitedBy->name,
                        'created_at'  => $invitation->created_at->format('d.m.Y H:i'),
                    ];
                }),
            ],
        ]);
    }

    public function update(UpdateMemberRequest $request, DataGrid $dataGrid, DataGridMember $member): JsonResponse
    {
        $this->authorize('manage', $dataGrid);

        if ($member->data_grid_id !== $dataGrid->id) {
            return response()->json([
                'success' => false,
                'message' => 'Участник не найден в этой таблице',
            ], 404);
        }

        $oldValues = $member->only(['permissions']);
        $member->update($request->validated());
        $newValues = $member->only(['permissions']);

        // Логирование обновления прав участника
        if ($oldValues !== $newValues) {
            $dataGrid->logAction(
                'member_updated',
                'Обновлены права участника',
                $member->user->id,
                $oldValues,
                $newValues,
                ['member_id' => $member->id]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Права участника обновлены',
            'data'    => new DataGridMemberResource($member),
        ]);
    }

    public function destroy(DataGrid $dataGrid, DataGridMember $member): JsonResponse
    {
        $this->authorize('manage', $dataGrid);

        if ($member->data_grid_id !== $dataGrid->id) {
            return response()->json([
                'success' => false,
                'message' => 'Участник не найден в этой таблице',
            ], 404);
        }

        // Логирование удаления участника
        $dataGrid->logAction(
            'member_removed',
            'Участник удален из таблицы',
            $member->user->id,
            ['permissions' => $member->permissions],
            [],
            ['member_id' => $member->id, 'removed_by' => auth()->user()->name]
        );

        $member->delete();

        return response()->json([
            'success' => true,
            'message' => 'Участник удален из таблицы',
        ]);
    }

    public function leave(DataGrid $dataGrid): JsonResponse
    {
        $member = $dataGrid->members()
            ->where('user_id', auth()->id())
            ->first();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Вы не являетесь участником этой таблицы',
            ], 404);
        }

        // Логирование выхода из таблицы
        $dataGrid->logAction(
            'member_left',
            'Участник покинул таблицу',
            auth()->user()->id,
            ['permissions' => $member->permissions],
            [],
            ['member_id' => $member->id, 'self_removed' => true]
        );

        $member->delete();

        return response()->json([
            'success' => true,
            'message' => 'Вы покинули таблицу',
        ]);
    }
}
