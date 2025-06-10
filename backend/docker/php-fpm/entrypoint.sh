#!/bin/bash
set -e

log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1"
}

log "Инициализация storage директорий..."

# Создаем все необходимые директории
mkdir -p /var/www/storage/{app/{public,framework},framework/{sessions,views,cache/{data,repository}},logs}
mkdir -p /var/www/bootstrap/cache

# Копируем начальную структуру, если storage пустой
if [ ! -f "/var/www/storage/.gitignore" ] && [ -d "/var/www/storage-init" ]; then
    log "Инициализация пустого storage..."
    cp -r /var/www/storage-init/* /var/www/storage/ 2>/dev/null || true
fi

# Устанавливаем права в зависимости от контекста
CURRENT_USER=$(whoami)
if [ "$CURRENT_USER" = "root" ]; then
    log "Установка ownership для www-data..."
    chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache
elif [ "$CURRENT_USER" = "www-data" ]; then
    log "Установка прав доступа..."
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true
else
    log "Пользователь: $CURRENT_USER, пропускаем изменение прав"
fi

log "Storage инициализация завершена"
exec "$@"
