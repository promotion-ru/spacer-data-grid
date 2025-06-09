<?php

namespace App\Policies;

use App\Models\DataGrid;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DataGridPolicy
{
    use HandlesAuthorization;

    /**
     * Проверка прав на просмотр таблицы
     */
    public function view(User $user, DataGrid $dataGrid): bool
    {
        // Базовая проверка разрешения на просмотр таблиц
        if (!$user->can('table.view')) {
            return false;
        }

        // Владелец таблицы может просматривать
        if ($dataGrid->isOwner($user)) {
            return true;
        }

        // Приглашенный пользователь может просматривать
        return $dataGrid->hasAccess($user);
    }

    /**
     * Проверка прав на создание таблиц
     */
    public function create(User $user): bool
    {
        return $user->can('table.create');
    }

    /**
     * Проверка прав на редактирование таблицы
     */
    public function update(User $user, DataGrid $dataGrid): bool
    {
        // Базовая проверка разрешения на редактирование таблиц
        if (!$user->can('table.edit')) {
            return false;
        }

        // Только владелец может редактировать саму таблицу (настройки, название и т.д.)
        return $dataGrid->isOwner($user);
    }

    /**
     * Проверка прав на удаление таблицы
     */
    public function delete(User $user, DataGrid $dataGrid): bool
    {
        // Базовая проверка разрешения на удаление таблиц
        if (!$user->can('table.delete')) {
            return false;
        }

        // Только владелец может удалить таблицу
        return $dataGrid->isOwner($user);
    }

    /**
     * Проверка прав на совместное использование таблицы
     */
    public function share(User $user, DataGrid $dataGrid): bool
    {
        // Базовая проверка разрешения на совместное использование
        if (!$user->can('table.share')) {
            return false;
        }

        // Только владелец может делиться таблицей
        return $dataGrid->isOwner($user);
    }

    /**
     * Проверка прав на управление участниками
     */
    public function manage(User $user, DataGrid $dataGrid): bool
    {
        // Базовая проверка разрешения на совместное использование
        if (!$user->can('table.manage')) {
            return false;
        }

        // Только владелец может управлять участниками
        return $dataGrid->isOwner($user);
    }

    /**
     * Проверка прав на просмотр списка таблиц
     */
    public function viewAny(User $user): bool
    {
        return $user->can('table.view');
    }

    /**
     * Проверка прав на создание записей в таблице
     */
    public function createRecord(User $user, DataGrid $dataGrid): bool
    {
        // Базовая проверка разрешения на просмотр таблиц
        if (!$user->can('table.view')) {
            return false;
        }

        // Владелец может создавать записи
        if ($dataGrid->isOwner($user)) {
            return true;
        }

        // Приглашенный пользователь может создавать записи, если есть права
        return $dataGrid->canUserPerform($user, 'create');
    }

    /**
     * Проверка прав на редактирование записей в таблице
     */
    public function updateRecord(User $user, DataGrid $dataGrid): bool
    {
        // Базовая проверка разрешения на просмотр таблиц
        if (!$user->can('table.view')) {
            return false;
        }

        // Владелец может редактировать записи
        if ($dataGrid->isOwner($user)) {
            return true;
        }

        // Приглашенный пользователь может редактировать записи, если есть права
        return $dataGrid->canUserPerform($user, 'update');
    }

    /**
     * Проверка прав на удаление записей в таблице
     */
    public function deleteRecord(User $user, DataGrid $dataGrid): bool
    {
        // Базовая проверка разрешения на просмотр таблиц
        if (!$user->can('table.view')) {
            return false;
        }

        // Владелец может удалять записи
        if ($dataGrid->isOwner($user)) {
            return true;
        }

        // Приглашенный пользователь может удалять записи, если есть права
        return $dataGrid->canUserPerform($user, 'delete');
    }

    /**
     * Проверка прав на покидание таблицы
     */
    public function leave(User $user, DataGrid $dataGrid): bool
    {
        // Владелец не может покинуть свою таблицу
        if ($dataGrid->isOwner($user)) {
            return false;
        }

        // Пользователь может покинуть таблицу, если он является участником
        return $dataGrid->isMember($user);
    }
}
