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

echo "Начинаем развертывание с минимальным downtime..."

# 1. Сборка новых образов БЕЗ остановки контейнеров
echo "Собираем новые образы..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml build --parallel backend frontend

# 2. Быстрая замена контейнеров (минимальный downtime)
echo "Быстро заменяем контейнеры..."

# Останавливаем только приложение, оставляем БД и Redis
docker-compose -f docker-compose.yml -f docker-compose.prod.yml stop frontend backend scheduler

# Удаляем старые контейнеры приложения
docker-compose -f docker-compose.yml -f docker-compose.prod.yml rm -f frontend backend scheduler

# Запускаем новые контейнеры
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d backend frontend scheduler

# 3. Ждем готовности
echo "Ожидаем готовности приложения..."
sleep 10

# Проверяем статус backend
timeout 60 bash -c 'until docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php -v > /dev/null 2>&1; do sleep 2; echo "Ждем backend..."; done'

if [ $? -ne 0 ]; then
    echo "ERROR: Backend не стартовал в течение 60 секунд"
    echo "Логи backend:"
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs backend
    exit 1
fi

# 4. Выполняем миграции
echo "Выполняем миграции..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan migrate --force

# 5. Очистка и оптимизация кеша
echo "Очищаем кеш..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan cache:clear
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan config:cache
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan route:cache
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan view:cache

# 6. Перезагружаем nginx
echo "Перезагружаем nginx..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec nginx nginx -s reload

# 7. Очистка неиспользуемых образов
echo "Очищаем старые образы..."
docker image prune -f

# 8. Финальная проверка
echo "Проверяем работоспособность..."
sleep 5

if docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps | grep -q "Up"; then
    echo "✅ Развертывание завершено успешно!"
    echo "Статус контейнеров:"
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps
else
    echo "❌ Ошибка в развертывании!"
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs
fi