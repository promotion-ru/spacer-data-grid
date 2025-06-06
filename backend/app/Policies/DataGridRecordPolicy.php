<?php

namespace App\Policies;

use App\Models\DataGrid;
use App\Models\DataGridRecord;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DataGridRecordPolicy
{
    use HandlesAuthorization;

    /**
     * Проверка прав на просмотр записи
     */
    public function view(User $user, DataGridRecord $record): bool
    {
        // Базовая проверка разрешения на просмотр таблиц
        if (!$user->can('table.view')) {
            return false;
        }

        $dataGrid = $record->dataGrid;

        // Владелец таблицы может просматривать все записи
        if ($dataGrid->isOwner($user)) {
            return true;
        }

        // Приглашенный пользователь может просматривать записи, если есть доступ к таблице
        return $dataGrid->hasAccess($user);
    }

    /**
     * Проверка прав на создание записи в таблице
     */
    public function create(User $user, DataGrid $dataGrid): bool
    {
        // Базовая проверка разрешения на просмотр таблиц
        if (!$user->can('table.view')) {
            return false;
        }

        // Владелец таблицы может создавать записи
        if ($dataGrid->isOwner($user)) {
            return true;
        }

        // Приглашенный пользователь может создавать записи, если есть соответствующие права
        return $dataGrid->canUserPerform($user, 'create');
    }

    /**
     * Проверка прав на просмотр списка записей в таблице
     */
    public function viewAny(User $user, DataGrid $dataGrid): bool
    {
        // Базовая проверка разрешения на просмотр таблиц
        if (!$user->can('table.view')) {
            return false;
        }

        // Владелец таблицы может просматривать все записи
        if ($dataGrid->isOwner($user)) {
            return true;
        }

        // Приглашенный пользователь может просматривать записи, если есть доступ к таблице
        return $dataGrid->hasAccess($user);
    }

    /**
     * Проверка прав на восстановление записи
     */
    public function restore(User $user, DataGridRecord $record): bool
    {
        return $this->delete($user, $record);
    }

    /**
     * Проверка прав на удаление записи
     */
    public function delete(User $user, DataGridRecord $record): bool
    {
        // Базовая проверка разрешения на просмотр таблиц
        if (!$user->can('table.view')) {
            return false;
        }

        $dataGrid = $record->dataGrid;

        // Владелец таблицы может удалять все записи
        if ($dataGrid->isOwner($user)) {
            return true;
        }

        // Автор записи может удалить свою запись, если у него есть права на удаление
        if ($record->user_id === $user->id && $dataGrid->canUserPerform($user, 'delete')) {
            return true;
        }

        // Приглашенный пользователь может удалять записи, если есть соответствующие права
        return $dataGrid->canUserPerform($user, 'delete');
    }

    /**
     * Проверка прав на окончательное удаление записи
     */
    public function forceDelete(User $user, DataGridRecord $record): bool
    {
        // Базовая проверка разрешения на удаление таблиц
        if (!$user->can('table.delete')) {
            return false;
        }

        $dataGrid = $record->dataGrid;

        // Только владелец таблицы может окончательно удалять записи
        return $dataGrid->isOwner($user);
    }

    /**
     * Проверка прав на работу с вложениями записи
     */
    public function manageAttachments(User $user, DataGridRecord $record): bool
    {
        // Используем ту же логику, что и для редактирования записи
        return $this->update($user, $record);
    }

    /**
     * Проверка прав на редактирование записи
     */
    public function update(User $user, DataGridRecord $record): bool
    {
        // Базовая проверка разрешения на просмотр таблиц
        if (!$user->can('table.view')) {
            return false;
        }
        if (!$user->can('table.edit')) {
            return false;
        }

        $dataGrid = $record->dataGrid;

        // Владелец таблицы может редактировать все записи
        if ($dataGrid->isOwner($user)) {
            return true;
        }

        // Автор записи может редактировать свою запись, если у него есть права на редактирование
        if ($record->user_id === $user->id && $dataGrid->canUserPerform($user, 'update')) {
            return true;
        }

        // Приглашенный пользователь может редактировать записи, если есть соответствующие права
        return $dataGrid->canUserPerform($user, 'update');
    }
}
