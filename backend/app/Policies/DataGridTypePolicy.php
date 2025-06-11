<?php

namespace App\Policies;

use App\Models\DataGridType;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DataGridTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('types.view');
    }

    public function view(User $user, DataGridType $dataGridType): bool
    {
        return $user->can('types.view');
    }

    public function create(User $user): bool
    {
        return $user->can('types.create');
    }

    public function update(User $user, DataGridType $dataGridType): bool
    {
        return $user->can('types.edit');
    }

    public function delete(User $user, DataGridType $dataGridType): bool
    {
        // Проверяем базовое разрешение на удаление
        if (!$user->can('types.delete')) {
            return false;
        }

        // Проверяем, не используется ли тип в записях
        if ($dataGridType->dataGridRecords()->exists()) {
            return false;
        }

        return true;
    }
}
