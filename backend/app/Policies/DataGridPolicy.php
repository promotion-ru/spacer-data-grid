<?php

namespace App\Policies;

use App\Models\DataGrid;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DataGridPolicy
{
    use HandlesAuthorization;

    public function view(User $user, DataGrid $dataGrid): bool
    {
        return $user->can('table.view') && $dataGrid->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('table.create');
    }

    public function update(User $user, DataGrid $dataGrid): bool
    {
        return $user->can('table.edit') && $dataGrid->user_id === $user->id;
    }

    public function delete(User $user, DataGrid $dataGrid): bool
    {
        return $user->can('table.delete') && $dataGrid->user_id === $user->id;
    }

    public function share(User $user, DataGrid $dataGrid): bool
    {
        return $user->can('table.share') && $dataGrid->user_id === $user->id;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('table.view');
    }
}
