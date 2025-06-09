#!/bin/bash

# Загрузка переменных окружения
set -a
source .env.production
set +a

# Проверка переменных
if [ -z "$MYSQL_ROOT_PASSWORD" ] || [ -z "$MYSQL_PASSWORD" ]; then
    echo "ERROR: MYSQL_ROOT_PASSWORD and MYSQL_PASSWORD must be set in .env.production"
    exit 1
fi

# Остановка существующих контейнеров
docker-compose -f docker-compose.yml -f docker-compose.prod.yml down

# Сборка и запуск контейнеров
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build

# Ожидание запуска
sleep 10

# Миграции базы данных
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec backend php artisan migrate --force

# Создание символической ссылки для storage
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec backend php artisan storage:link

# Очистка и оптимизация кеша
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec backend php artisan cache:clear
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec backend php artisan config:cache
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec backend php artisan route:cache
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec backend php artisan view:cache

echo "Развертывание завершено!"