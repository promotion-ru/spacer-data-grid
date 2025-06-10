#!/bin/bash
set -e

# Функция для логирования
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1"
}

log "Запуск PHP-FPM entrypoint скрипта..."

# Проверяем текущего пользователя
CURRENT_USER=$(whoami)
log "Запуск от имени пользователя: $CURRENT_USER"

# Инициализация содержимого storage, если оно пустое
# (директории уже созданы в Dockerfile)
if [ ! -f "/var/www/storage/.gitignore" ] && [ -d "/var/www/storage-init" ]; then
    log "Инициализация содержимого storage..."
    cp -r /var/www/storage-init/* /var/www/storage/ 2>/dev/null || true
    log "Содержимое storage инициализировано"
fi

# Дополнительные директории могут понадобиться в runtime
# Используем 2>/dev/null || true чтобы не падать если уже существуют
mkdir -p /var/www/storage/framework/cache/{data,repository} 2>/dev/null || true
mkdir -p /var/www/storage/app/public 2>/dev/null || true

# Проверяем и устанавливаем права (только если возможно)
if [ -w "/var/www/storage" ]; then
    chmod -R 775 /var/www/storage 2>/dev/null || true
    log "Права доступа для storage проверены"
fi

if [ -w "/var/www/bootstrap/cache" ]; then
    chmod -R 775 /var/www/bootstrap/cache 2>/dev/null || true
    log "Права доступа для bootstrap/cache проверены"
fi

log "Entrypoint завершен успешно, запуск PHP-FPM..."

# Выполнение основной команды
exec "$@"
