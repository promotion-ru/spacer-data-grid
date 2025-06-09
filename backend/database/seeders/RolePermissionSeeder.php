<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Создаем базовые роли
        $admin = Role::create(['name' => 'administrator']);
        $user = Role::create(['name' => 'user']);

        // Создаем разрешения по группам ресурсов
        $this->createUserPermissions();

        // Назначаем права ролям
        $this->assignPermissionsToRoles();
    }

    private function createUserPermissions()
    {
        Permission::create(['name' => 'users.view']);
        Permission::create(['name' => 'users.create']);
        Permission::create(['name' => 'users.edit']);
        Permission::create(['name' => 'users.delete']);

        Permission::create(['name' => 'table.view']);
        Permission::create(['name' => 'table.create']);
        Permission::create(['name' => 'table.edit']);
        Permission::create(['name' => 'table.delete']);
        Permission::create(['name' => 'table.share']);
        Permission::create(['name' => 'table.manage']);
        Permission::create(['name' => 'table.view.logs']);

        Permission::create(['name' => 'system.settings']);
        Permission::create(['name' => 'system.logs']);
        Permission::create(['name' => 'system.maintenance']);
    }

    private function assignPermissionsToRoles()
    {
        // Admin - все права
        Role::findByName('administrator')->givePermissionTo(Permission::all());

        // User - управление контентом и пользователями
        Role::findByName('user')->givePermissionTo([
            'table.view', 'table.create', 'table.edit', 'table.delete', 'table.share'
        ]);

    }
}
