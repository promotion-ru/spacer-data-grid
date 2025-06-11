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

# Проверка состояния MySQL тома
if ! docker volume inspect spacer-data-grid_mysql_prod_data >/dev/null 2>&1; then
    echo "Creating new MySQL volume..."
    docker volume create spacer-data-grid_mysql_prod_data
fi

echo "Начинаем zero-downtime развертывание..."

# 1. Сборка новых образов БЕЗ остановки текущих контейнеров
echo "Собираем новые образы..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml build --no-cache backend frontend

# 2. Обновляем backend с rolling update
echo "Обновляем backend..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --no-deps --force-recreate backend

# Ждем готовности backend
echo "Ожидаем готовности backend..."
timeout 60 bash -c 'until docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan tinker --execute="echo \"Backend ready\";" > /dev/null 2>&1; do sleep 2; done'

if [ $? -ne 0 ]; then
    echo "ERROR: Backend не стартовал в течение 60 секунд"
    exit 1
fi

# 3. Выполняем миграции
echo "Выполняем миграции..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan migrate --force

# 4. Обновляем scheduler
echo "Обновляем scheduler..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --no-deps --force-recreate scheduler

# 5. Обновляем frontend
echo "Обновляем frontend..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --no-deps --force-recreate frontend

# Ждем готовности frontend
echo "Ожидаем готовности frontend..."
timeout 60 bash -c 'until curl -f http://localhost:3000/api/health > /dev/null 2>&1; do sleep 2; done'

# 6. Перезагружаем nginx для обновления upstream
echo "Обновляем nginx..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec nginx nginx -s reload

# 7. Очистка и оптимизация кеша
echo "Очищаем кеш..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan cache:clear
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan config:cache
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan route:cache
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan view:cache

# 8. Очистка неиспользуемых образов
echo "Очищаем старые образы..."
docker image prune -f

echo "Zero-downtime развертывание завершено успешно!"