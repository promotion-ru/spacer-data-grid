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

# НОВОЕ: Функция аварийного перезапуска
emergency_restart() {
    local error_message="$1"
    echo "🚨 КРИТИЧЕСКАЯ ОШИБКА: $error_message"
    echo "🔄 Выполняем полный перезапуск системы..."

    # Логируем состояние перед перезапуском
    echo "Состояние контейнеров перед перезапуском:"
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps

    echo "Останавливаем все контейнеры..."
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml down

    # Даем время на полную остановку
    sleep 5

    echo "Запускаем полную пересборку и подъем системы..."
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build

    # Ждем стабилизации системы
    echo "Ожидаем стабилизации системы после полного перезапуска..."
    sleep 30

    # Проверяем что все поднялось
    echo "Проверяем состояние после аварийного перезапуска:"
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps

    # Проверяем доступность основных сервисов
    check_services_after_restart
}

# НОВОЕ: Функция проверки сервисов после аварийного перезапуска
check_services_after_restart() {
    echo "Проверяем восстановление сервисов..."

    # Проверка MySQL
    echo "Проверяем MySQL..."
    timeout 120 bash -c 'until docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T mysql mysqladmin ping -h localhost -u root -p${MYSQL_ROOT_PASSWORD} --silent; do
        sleep 3
        echo "Ждем восстановления MySQL..."
    done'

    if [ $? -ne 0 ]; then
        echo "❌ MySQL не восстановился после аварийного перезапуска"
        docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs mysql
        exit 1
    fi

    # Проверка Backend
    echo "Проверяем восстановление backend..."
    timeout 120 bash -c 'until docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan tinker --execute="DB::connection()->getPdo(); echo \"DB OK\";" > /dev/null 2>&1; do
        sleep 3
        echo "Ждем восстановления backend..."
    done'

    if [ $? -ne 0 ]; then
        echo "❌ Backend не восстановился после аварийного перезапуска"
        docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs backend
        exit 1
    fi

    # Проверка Frontend
    echo "Проверяем восстановление frontend..."
    timeout 90 bash -c 'until docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs frontend 2>/dev/null | grep -q "Listening on http://0.0.0.0:3000"; do
        sleep 2
        echo "Ждем восстановления frontend..."
    done'

    if [ $? -ne 0 ]; then
        echo "❌ Frontend не восстановился после аварийного перезапуска"
        docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs frontend
        exit 1
    fi

    # Финальная проверка доступности сайта
    echo "Финальная проверка доступности сайта..."
    timeout 60 bash -c 'until curl -f http://localhost/ > /dev/null 2>&1; do
        sleep 2
        echo "Проверяем доступность сайта..."
    done'

    if [ $? -eq 0 ]; then
        echo "✅ Аварийное восстановление завершено успешно!"

        # Выполняем критически важные команды после восстановления
        echo "Выполняем миграции после восстановления..."
        docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan migrate --force

        echo "Очищаем кеш после восстановления..."
        docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan cache:clear
        docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan config:cache
        docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan route:cache
        docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan view:cache

        echo "✅ Система полностью восстановлена и готова к работе!"
    else
        echo "❌ Критическая ошибка: сайт недоступен даже после аварийного перезапуска!"
        echo "Логи всех сервисов:"
        docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs --tail=20
        exit 1
    fi
}

# Очистка поврежденных образов MySQL
echo "Проверяем целостность образов MySQL..."
if ! docker inspect mysql:8.0.35 >/dev/null 2>&1; then
    echo "Образ MySQL поврежден или отсутствует, принудительно загружаем новый..."
    docker rmi $(docker images | grep mysql | awk '{print $3}') --force 2>/dev/null || true
    docker pull mysql:8.0.35

    if [ $? -ne 0 ]; then
        emergency_restart "Не удалось загрузить образ MySQL"
        exit 0  # emergency_restart завершит выполнение
    fi
fi

# Проверка состояния MySQL тома
if ! docker volume inspect spacer-data-grid_mysql_prod_data >/dev/null 2>&1; then
    echo "Creating new MySQL volume..."
    docker volume create spacer-data-grid_mysql_prod_data

    if [ $? -ne 0 ]; then
        emergency_restart "Не удалось создать том MySQL"
        exit 0
    fi
fi

echo "Начинаем развертывание с минимальным downtime..."

# 1. Сборка новых образов БЕЗ остановки контейнеров
echo "Собираем новые образы..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml build --parallel backend frontend

if [ $? -ne 0 ]; then
    emergency_restart "Ошибка при сборке образов backend/frontend"
    exit 0
fi

# 2. Специальная обработка MySQL
echo "Проверяем состояние MySQL контейнера..."
if docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps mysql | grep -q "Up"; then
    echo "MySQL работает нормально, не трогаем"
else
    echo "Пересоздаем MySQL контейнер..."
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d mysql

    if [ $? -ne 0 ]; then
        emergency_restart "Ошибка при запуске MySQL контейнера"
        exit 0
    fi

    # Ждем готовности MySQL
    echo "Ожидаем готовности MySQL..."
    timeout 120 bash -c 'until docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T mysql mysqladmin ping -h localhost -u root -p${MYSQL_ROOT_PASSWORD} --silent; do
        sleep 3
        echo "Ждем MySQL..."
    done'

    if [ $? -ne 0 ]; then
        emergency_restart "MySQL не готов после пересоздания"
        exit 0
    fi
fi

# 3. Быстрая замена контейнеров приложения
echo "Быстро заменяем контейнеры приложения..."

# Останавливаем только приложение, оставляем БД, Redis и nginx
docker-compose -f docker-compose.yml -f docker-compose.prod.yml stop frontend backend scheduler

if [ $? -ne 0 ]; then
    emergency_restart "Ошибка при остановке контейнеров приложения"
    exit 0
fi

# Удаляем старые контейнеры приложения
docker-compose -f docker-compose.yml -f docker-compose.prod.yml rm -f frontend backend scheduler

# Запускаем новые контейнеры
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d backend frontend scheduler

if [ $? -ne 0 ]; then
    emergency_restart "Ошибка при запуске новых контейнеров приложения"
    exit 0
fi

# 4. Ждем готовности контейнеров
echo "Ожидаем готовности контейнеров..."
sleep 10

# Проверяем готовность backend с подключением к MySQL
echo "Проверяем backend..."
timeout 90 bash -c 'until docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan tinker --execute="DB::connection()->getPdo(); echo \"DB OK\";" > /dev/null 2>&1; do
    sleep 3
    echo "Ждем backend и подключение к БД..."
done'

if [ $? -ne 0 ]; then
    emergency_restart "Backend не готов или нет связи с БД"
    exit 0
fi

echo "Проверяем frontend..."
timeout 60 bash -c 'until docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs frontend 2>/dev/null | grep -q "Listening on http://0.0.0.0:3000"; do
    sleep 2
    echo "Ждем frontend..."
done'

if [ $? -ne 0 ]; then
    emergency_restart "Frontend не готов"
    exit 0
fi

# Дополнительная проверка через внешний доступ
echo "Проверяем внешнюю доступность frontend через nginx..."
sleep 5

# КРИТИЧНО: Перезапускаем nginx для обновления DNS кеша
echo "Перезапускаем nginx для обновления DNS кеша..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml restart nginx

if [ $? -ne 0 ]; then
    emergency_restart "Ошибка при перезапуске nginx"
    exit 0
fi

sleep 5

# 5. Выполняем миграции
echo "Выполняем миграции..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan migrate --force

if [ $? -ne 0 ]; then
    emergency_restart "Ошибка при выполнении миграций"
    exit 0
fi

# 6. Очистка и оптимизация кеша
echo "Очищаем кеш..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan cache:clear
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan config:cache
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan route:cache
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan view:cache

if [ $? -ne 0 ]; then
    emergency_restart "Ошибка при очистке кеша"
    exit 0
fi

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
    emergency_restart "Сайт недоступен после развертывания"
    exit 0
fi

# 8. Очистка старых образов
echo "Очищаем старые образы..."
docker image prune -f

echo "✅ Развертывание завершено!"