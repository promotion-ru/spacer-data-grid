#!/bin/bash
set -e

# Функция для логирования
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1"
}

log "Запуск PHP-FPM entrypoint скрипта..."

# Инициализация структуры storage, если она не существует
if [ ! -d "/var/www/storage/logs" ]; then
    log "Инициализация структуры storage..."
    cp -r /var/www/storage-init/* /var/www/storage/
fi

# Создание необходимых директорий storage
mkdir -p /var/www/storage/{app,framework/{sessions,views,cache/data},logs}

# Установка правильных прав доступа для storage и bootstrap/cache
if [ -w "/var/www/storage" ]; then
    chmod -R 775 /var/www/storage
    log "Права доступа для storage установлены"
fi

#if [ -w "/var/www/bootstrap/cache" ]; then
#    chmod -R 775 /var/www/bootstrap/cache
#    log "Права доступа для bootstrap/cache установлены"
#fi

# Проверка переменных окружения Laravel
if [ -z "$APP_KEY" ]; then
    log "ПРЕДУПРЕЖДЕНИЕ: APP_KEY не установлен"
fi

if [ -z "$DB_CONNECTION" ]; then
    log "ПРЕДУПРЕЖДЕНИЕ: DB_CONNECTION не установлен"
fi

# Очистка кеша конфигурации (на случай, если он был закеширован в build stage)
if [ -f "/var/www/bootstrap/cache/config.php" ]; then
    log "Очистка кеша конфигурации..."
    rm -f /var/www/bootstrap/cache/config.php
fi

# Проверка доступности базы данных (опционально)
if [ "$DB_CONNECTION" = "mysql" ] && [ -n "$DB_HOST" ]; then
    log "Проверка подключения к MySQL..."
    # Здесь можно добавить проверку подключения к БД
fi

log "Entrypoint завершен, запуск PHP-FPM..."

# Выполнение основной команды
exec "$@"
