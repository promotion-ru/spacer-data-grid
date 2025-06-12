#!/bin/bash

# --- НАСТРОЙКИ ---
# Укажите здесь пути к вашим docker-compose файлам
COMPOSE_FILES="-f docker-compose.yml -f docker-compose.prod.yml"

# Имя файла с переменными окружения
ENV_FILE=".env"
# -----------------

# Немедленно выходить, если команда завершилась с ошибкой
set -e

echo "🚀 Начинаем процесс развертывания..."

# 1. ПРОВЕРКИ ПЕРЕД ЗАПУСКОМ
# Проверяем наличие файла с переменными окружения
if [ ! -f "$ENV_FILE" ]; then
    echo "❌ Ошибка: Файл окружения '$ENV_FILE' не найден!"
    exit 1
fi

# Проверяем, что Docker демон запущен и работает
if ! docker info > /dev/null 2>&1; then
    echo "❌ Ошибка: Docker демон не запущен. Запустите Docker и попробуйте снова."
    exit 1
fi

# Загружаем переменные окружения для использования в скрипте
set -a
source $ENV_FILE
set +a

echo "✅ Проверки пройдены."

# 2. ОБНОВЛЕНИЕ БАЗОВЫХ ОБРАЗОВ
echo "🔄 Обновляем базовые образы (mysql, redis, php, etc)..."
docker-compose $COMPOSE_FILES pull

# 3. СБОРКА НОВЫХ ОБРАЗОВ ПРИЛОЖЕНИЯ
echo "🛠️  Собираем новые образы для backend и frontend..."
docker-compose $COMPOSE_FILES build --parallel backend frontend

# 4. РАЗВЕРТЫВАНИЕ КОНТЕЙНЕРОВ
echo "🚢 Запускаем обновленные контейнеры..."
# Используем --force-recreate, чтобы пересоздать контейнеры, чьи образы обновились.
# --no-deps предотвращает пересоздание зависимостей (например, БД), если они не изменились.
# -d запускает контейнеры в фоновом режиме.
docker-compose $COMPOSE_FILES up -d --force-recreate --no-deps backend frontend scheduler nginx

echo "✅ Контейнеры запущены. Ожидаем их готовности..."

# 5. ПРОВЕРКА ГОТОВНОСТИ СЕРВИСОВ
# Даем контейнерам несколько секунд на первоначальный запуск
sleep 5

# Проверяем готовность backend (простой проверкой, что PHP доступен)
echo "⏳ Проверяем готовность backend..."
timeout 120s bash -c 'until docker-compose '"$COMPOSE_FILES"' exec -T backend php -v > /dev/null 2>&1; do echo "   ...ожидаем backend..."; sleep 3; done'
if [ $? -ne 0 ]; then
    echo "❌ Ошибка: Backend не запустился вовремя. Проверьте логи:"
    docker-compose $COMPOSE_FILES logs --tail=50 backend
    exit 1
fi
echo "👍 Backend готов."

# Проверяем готовность frontend (ищем сообщение о том, что сервер слушает порт)
echo "⏳ Проверяем готовность frontend..."
timeout 120s bash -c 'until docker-compose '"$COMPOSE_FILES"' logs frontend 2>/dev/null | grep -q -i "listening"; do echo "   ...ожидаем frontend..."; sleep 3; done'
if [ $? -ne 0 ]; then
    echo "❌ Ошибка: Frontend не запустился вовремя. Проверьте логи:"
    docker-compose $COMPOSE_FILES logs --tail=50 frontend
    exit 1
fi
echo "👍 Frontend готов."

# 6. ЗАДАЧИ ПОСЛЕ РАЗВЕРТЫВАНИЯ
echo "⚙️  Выполняем задачи после развертывания..."

echo "   - Запускаем миграции базы данных..."
docker-compose $COMPOSE_FILES exec -T backend php artisan migrate --force

echo "   - Очищаем и кешируем конфигурацию и маршруты..."
docker-compose $COMPOSE_FILES exec -T backend php artisan cache:clear
docker-compose $COMPOSE_FILES exec -T backend php artisan config:cache
docker-compose $COMPOSE_FILES exec -T backend php artisan route:cache
docker-compose $COMPOSE_FILES exec -T backend php artisan view:cache

echo "✅ Задачи выполнены."

# 7. ОЧИСТКА
echo "🧹 Очищаем старые неиспользуемые образы..."
docker image prune -f

echo "🎉 Развертывание успешно завершено!"
echo "Текущий статус контейнеров:"
docker-compose $COMPOSE_FILES ps