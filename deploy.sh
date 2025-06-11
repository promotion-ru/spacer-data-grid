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

# Останавливаем только приложение, оставляем БД, Redis и nginx
docker-compose -f docker-compose.yml -f docker-compose.prod.yml stop frontend backend scheduler

# Удаляем старые контейнеры приложения
docker-compose -f docker-compose.yml -f docker-compose.prod.yml rm -f frontend backend scheduler

# Запускаем новые контейнеры
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d backend frontend scheduler

# 3. Ждем готовности контейнеров
echo "Ожидаем готовности контейнеров..."
sleep 10

# Проверяем готовность backend
echo "Проверяем backend..."
timeout 60 bash -c 'until docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php -v > /dev/null 2>&1; do
    sleep 2
    echo "Ждем backend..."
done'

if [ $? -ne 0 ]; then
    echo "ERROR: Backend не готов"
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs backend
    exit 1
fi

# Проверяем готовность frontend
echo "Проверяем frontend..."
timeout 60 bash -c 'until docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T frontend curl -f http://localhost:3000/ > /dev/null 2>&1; do
    sleep 2
    echo "Ждем frontend..."
done'

if [ $? -ne 0 ]; then
    echo "ERROR: Frontend не готов"
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs frontend
    exit 1
fi

# 4. КРИТИЧНО: Перезапускаем nginx для обновления DNS кеша
echo "Перезапускаем nginx для обновления DNS кеша..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml restart nginx

# Даем nginx время на старт
sleep 5

# 5. Выполняем миграции
echo "Выполняем миграции..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan migrate --force

# 6. Очистка и оптимизация кеша
echo "Очищаем кеш..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan cache:clear
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan config:cache
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan route:cache
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan view:cache

# 7. Финальная проверка доступности
echo "Финальная проверка доступности..."
timeout 30 bash -c 'until curl -f http://localhost/ > /dev/null 2>&1; do
    sleep 2
    echo "Проверяем доступность сайта..."
done'

if [ $? -eq 0 ]; then
    echo "✅ Развертывание завершено успешно!"
    echo "Сайт доступен!"

    # Показываем статус всех контейнеров
    echo "Статус контейнеров:"
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps
else
    echo "❌ Сайт недоступен после развертывания!"
    echo "Логи nginx:"
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs --tail=10 nginx
    exit 1
fi

# 8. Очистка старых образов
echo "Очищаем старые образы..."
docker image prune -f

echo "✅ Развертывание завершено!"