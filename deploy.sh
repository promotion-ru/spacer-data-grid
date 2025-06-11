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

# Ожидание запуска MySQL с проверкой здоровья
echo "Waiting for MySQL to be ready..."
timeout=60
counter=0
while [ $counter -lt $timeout ]; do
    if docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec mysql mysqladmin ping -h"localhost" -u"root" -p"${MYSQL_ROOT_PASSWORD}" --silent; then
        echo "MySQL is ready!"
        break
    fi
    echo "MySQL is not ready yet... ($counter/$timeout)"
    sleep 2
    counter=$((counter + 2))
done

if [ $counter -ge $timeout ]; then
    echo "ERROR: MySQL failed to start within $timeout seconds"
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs mysql
    exit 1
fi

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