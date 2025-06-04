<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class AssignUserRole extends Command
{
    protected $signature = 'user:assign-role {user_id} {role}';
    protected $description = 'Назначить роль пользователю';

    public function handle(): int
    {
        $userId = $this->argument('user_id');
        $roleName = $this->argument('role');

        // Находим пользователя
        $user = User::query()->find($userId);
        if (!$user) {
            $this->error("Пользователь с ID {$userId} не найден");
            return 1;
        }

        $role = Role::query()->where('name', $roleName)->first();
        if (!$role) {
            $this->error("Роль '{$roleName}' не существует");
            return 1;
        }

        $user->assignRole($roleName);

        $this->info("Роль '{$roleName}' успешно назначена пользователю {$user->name} (ID: {$user->id})");

        return 0;
    }
}
