#!/bin/bash

# -----------------------------------------------------------------------------
# Скрипт для безопасного развертывания приложения на боевом сервере
# Версия с универсальной проверкой готовности backend.
# -----------------------------------------------------------------------------

set -e

# --- 1. ПОДГОТОВКА ---
echo "▶️  Начинаем развертывание..."

set -a
if [ -f .env ]; then
    source .env
else
    echo "❌ ERROR: Файл .env не найден!"
    exit 1
fi
set +a

if [ -z "$MYSQL_ROOT_PASSWORD" ] || [ -z "$MYSQL_PASSWORD" ]; then
    echo "❌ ERROR: MYSQL_ROOT_PASSWORD и MYSQL_PASSWORD должны быть установлены в .env"
    exit 1
fi

echo "🔄  Обновляем базовые образы Docker..."
docker compose -f docker-compose.yml -f docker-compose.prod.yml pull

# --- 2. ЭТАП СБОРКИ ---
echo "🛠️  Пытаемся собрать новые образы..."
docker compose -f docker-compose.yml -f docker-compose.prod.yml build --parallel backend frontend scheduler
echo "✅ Сборка образов прошла успешно. Начинаем развертывание."

# --- 3. ЭТАП РАЗВЕРТЫВАНИЯ ---
echo "🚀  Обновляем сервисы до новых версий..."
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --remove-orphans backend frontend scheduler

# --- 4. ПРОВЕРКА ГОТОВНОСТИ СЕРВИСОВ ---
echo "⌛  Ожидаем готовности новых контейнеров..."
echo "Проверяем backend..."
# Используем curl для проверки главной страницы. Это надежно тестирует всю цепочку.
timeout 120s bash -c '
  until docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend curl -sf http://localhost/ > /dev/null 2>&1; do
    echo "Ждем, пока backend ответит на http://localhost/..."
    sleep 3
  done
' || {
  echo "❌ ERROR: Backend не готов после 120 секунд. Проверьте логи."
  docker compose -f docker-compose.yml -f docker-compose.prod.yml logs --tail=50 backend
  exit 1
}
echo "✅ Backend готов!"

# --- 5. МИГРАЦИИ И КЕШИРОВАНИЕ ---
echo "🛠️  Включаем режим обслуживания..."
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan down || true

echo "Выполняем миграции базы данных..."
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan migrate --force

echo "Очищаем и обновляем кеш приложения..."
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan optimize:clear
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan config:cache
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan route:cache
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan view:cache

echo "Выключаем режим обслуживания..."
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan up

# --- 6. ФИНАЛЬНЫЕ ШАГИ ---
echo "🔄  Перезапускаем Nginx для обновления внутреннего DNS кеша..."
docker compose -f docker-compose.yml -f docker-compose.prod.yml restart nginx

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
    docker compose -f docker-compose.yml -f docker-compose.prod.yml ps
else
    echo "❌ Сайт недоступен после развертывания!"
    echo "Логи nginx:"
    docker compose -f docker-compose.yml -f docker-compose.prod.yml logs --tail=10 nginx
    exit 1
fi

# 8. Очистка старых образов
echo "Очищаем старые образы..."
docker image prune -f

echo "✅ Развертывание завершено!"