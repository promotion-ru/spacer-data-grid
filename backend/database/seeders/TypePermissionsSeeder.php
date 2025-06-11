<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TypePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Создаем базовые права для типов
        $permissions = [
            ['name' => 'types.view', 'description' => 'Просмотр типов данных'],
            ['name' => 'types.create', 'description' => 'Создание типов данных'],
            ['name' => 'types.edit', 'description' => 'Редактирование типов данных'],
            ['name' => 'types.delete', 'description' => 'Удаление типов данных'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
            );
        }

        // Назначаем права ролям
        $adminRole = Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo([
                'types.view',
                'types.create',
                'types.edit',
                'types.delete'
            ]);
        }

        $userRole = Role::where('name', 'user')->first();
        if ($userRole) {
            $userRole->givePermissionTo([
                'types.view',
                'types.create',
                'types.edit',
                'types.delete',
            ]);
        }
    }
}
