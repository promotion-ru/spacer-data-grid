import type { User } from '~/types/auth'

export const usePermissions = () => {
    const { user } = useAuth()

    // ========== ПРОВЕРКИ РОЛЕЙ ==========

    // Проверка роли
    const hasRole = (role: string): boolean => {
        return user.value?.roles?.includes(role) || false
    }

    // Проверка нескольких ролей (хотя бы одна)
    const hasAnyRole = (roles: string[]): boolean => {
        if (!user.value?.roles) {
            return false
        }
        return roles.some(role => user.value.roles.includes(role))
    }

    // Проверка всех ролей (все должны быть)
    const hasAllRoles = (roles: string[]): boolean => {
        if (!user.value?.roles) {
            return false
        }
        return roles.every(role => user.value.roles.includes(role))
    }

    // ========== ПРОВЕРКИ РАЗРЕШЕНИЙ ==========

    // Проверка разрешения
    const hasPermission = (permission: string): boolean => {
        return user.value?.permissions?.includes(permission) || false
    }

    // Проверка нескольких разрешений (хотя бы одно)
    const hasAnyPermission = (permissions: string[]): boolean => {
        if (!user.value?.permissions) {
            return false
        }
        return permissions.some(permission => user.value.permissions.includes(permission))
    }

    // Проверка всех разрешений (все должны быть)
    const hasAllPermissions = (permissions: string[]): boolean => {
        if (!user.value?.permissions) {
            return false
        }
        return permissions.every(permission => user.value.permissions.includes(permission))
    }

    // ========== ПРОВЕРКИ ЧЕРЕЗ ГРУППИРОВАННЫЕ ПРАВА ==========

    // Проверка возможности выполнения действия (через группированные права)
    const can = (resource: keyof User['can'], action: string): boolean => {
        if (!user.value?.can || !user.value.can[resource]) {
            return false
        }
        return (user.value.can[resource] as any)[action] || false
    }

    // ========== БЫСТРЫЕ ПРОВЕРКИ РОЛЕЙ ==========

    // Проверка, является ли пользователь администратором
    const isAdmin = computed(() => {
        return user.value?.is_admin || false
    })

    // Проверка доступа к админ-панели
    const canAccessAdmin = computed(() => {
        return isAdmin.value
    })

    // ========== РЕАКТИВНЫЕ СВОЙСТВА ==========

    // Реактивные computed свойства для удобства
    const permissions = computed(() => user.value?.permissions || [])
    const roles = computed(() => user.value?.roles || [])

    // ========== ГРУППИРОВАННЫЕ ПРОВЕРКИ ПО РЕСУРСАМ ==========

    // Проверки для пользователей
    const users = computed(() => ({
        view: can('users', 'view'),
        create: can('users', 'create'),
        edit: can('users', 'edit'),
        delete: can('users', 'delete')
    }))

    // Проверки для таблиц
    const table = computed(() => ({
        view: can('table', 'view'),
        create: can('table', 'create'),
        edit: can('table', 'edit'),
        delete: can('table', 'delete'),
        share: can('table', 'share'),
        manage: can('table', 'manage'),
    }))

    // Проверки для системы
    const system = computed(() => ({
        settings: can('system', 'settings'),
        logs: can('system', 'logs'),
        maintenance: can('system', 'maintenance')
    }))

    // ========== ЧАСТО ИСПОЛЬЗУЕМЫЕ ПРОВЕРКИ ==========

    const canManageUsers = computed(() => users.value.edit || isAdmin.value)
    const canManageTable = computed(() => table.value.edit || isAdmin.value)
    const canAccessSystemSettings = computed(() => system.value.settings || isAdmin.value)

    return {
        // Основные свойства
        user,
        permissions,
        roles,

        // Методы проверки ролей
        hasRole,
        hasAnyRole,
        hasAllRoles,

        // Методы проверки разрешений
        hasPermission,
        hasAnyPermission,
        hasAllPermissions,

        // Проверка через группированные права
        can,

        // Быстрые проверки ролей
        isAdmin,
        canAccessAdmin,

        // Часто используемые проверки
        canManageUsers,
        canManageTable,
        canAccessSystemSettings,

        // Проверки по ресурсам
        users,
        table,
        system
    }
}