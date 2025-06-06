<?php

namespace App\Policies;

use App\Models\DataGrid;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function view(User $user, User $modelUser): bool
    {
        return $user->can('users.view') && $user->hasRole('administrator');
    }

    public function store(User $user): bool
    {
        return $user->can('users.create') && $user->hasRole('administrator');
    }

    public function update(User $user, DataGrid $modelUser): bool
    {
        return $user->can('users.edit') && $user->hasRole('administrator');
    }

    public function delete(User $user, DataGrid $modelUser): bool
    {
        return $user->can('users.delete') && $user->hasRole('administrator');
    }

    public function viewAny(User $user): bool
    {
        return $user->can('users.view') && $user->hasRole('administrator');
    }
}
