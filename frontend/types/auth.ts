// types/auth.ts
export interface User {
    id: number
    name: string
    surname: string | null
    email: string
    email_verified_at: string | null
    avatar?: string | null
    created_at: string
    updated_at: string

    // Роли и права
    roles: string[]
    permissions: string[]

    // Быстрые проверки
    is_admin: boolean
    is_manager: boolean

    // Группированные права
    can: {
        users: {
            view: boolean
            create: boolean
            edit: boolean
            delete: boolean
        }
        table: {
            view: boolean
            create: boolean
            edit: boolean
            delete: boolean
        }
        system: {
            settings: boolean
            logs: boolean
            maintenance: boolean
        }
    }
}

export interface TokenInfo {
    id: number
    name: string
    abilities: string[]
    last_used_at: string | null
    expires_at: string | null
    created_at: string
    is_current: boolean
    is_expired: boolean
}