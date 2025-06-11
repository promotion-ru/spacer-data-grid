#!/bin/bash

# Загрузка переменных окружения
set -a
source .env
set +a

# Проверка переменных
if [ -z "$MYSQL_ROOT_PASSWORD" ] || [ -z "$MYSQL_PASSWORD" ]; then
    echo "ERROR: MYSQL_ROOT_PASSWORD and MYSQL_PASSWORD must be set in .env"
    exit 1
fi

# Остановка существующих контейнеров
docker-compose -f docker-compose.yml -f docker-compose.prod.yml down

# Проверка состояния MySQL тома
if ! docker volume inspect spacer-data-grid_mysql_prod_data >/dev/null 2>&1; then
    echo "Creating new MySQL volume..."
    docker volume create spacer-data-grid_mysql_prod_data
fi

# Сборка и запуск контейнеров
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build

# Миграции базы данных
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec backend php artisan migrate --force

# Очистка и оптимизация кеша
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec backend php artisan cache:clear
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec backend php artisan config:cache
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec backend php artisan route:cache
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec backend php artisan view:cache

echo "Развертывание завершено!"