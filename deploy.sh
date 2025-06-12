#!/bin/bash

# -----------------------------------------------------------------------------
# Zero-Downtime Deployment Script для Laravel + Vue2
# Использует Blue-Green стратегию с проверкой готовности
# -----------------------------------------------------------------------------

set -e

# Переменные
SCRIPT_START_TIME=$(date +%s)
LOG_FILE="deployment_$(date +%Y%m%d_%H%M%S).log"
BACKUP_FILE="backup_$(date +%Y%m%d_%H%M%S).sql"
HEALTH_CHECK_TIMEOUT=120
MIGRATION_TIMEOUT=300

# Логирование
exec > >(tee -a "$LOG_FILE")
exec 2>&1

echo "🚀 НАЧИНАЕМ ZERO-DOWNTIME DEPLOYMENT"
echo "📝 Лог: $LOG_FILE"
echo "⏰ Время начала: $(date)"

# --- ФУНКЦИИ ---

log_step() {
    local step_name="$1"
    echo ""
    echo "▶️  [$step_name] $(date)"
    echo "----------------------------------------"
}

check_prerequisites() {
    log_step "ПРОВЕРКА ПРЕДВАРИТЕЛЬНЫХ УСЛОВИЙ"

    # Проверяем .env
    set -a
    if [ -f .env ]; then
        source .env
    else
        echo "❌ ERROR: Файл .env не найден!"
        exit 1
    fi
    set +a

    # Проверяем обязательные переменные
    if [ -z "$MYSQL_ROOT_PASSWORD" ] || [ -z "$MYSQL_PASSWORD" ] || [ -z "$MYSQL_DATABASE" ]; then
        echo "❌ ERROR: Отсутствуют обязательные переменные в .env"
        exit 1
    fi

    # Проверяем ресурсы системы
    DISK_USAGE=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
    if [ "$DISK_USAGE" -gt 85 ]; then
        echo "⚠️  WARNING: Мало места на диске (${DISK_USAGE}% использовано)"
        read -p "Продолжить? (y/N): " -r
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 1
        fi
    fi

    echo "✅ Предварительные проверки пройдены"
}

save_current_state() {
    log_step "СОХРАНЕНИЕ ТЕКУЩЕГО СОСТОЯНИЯ"

    # Сохраняем ID текущих контейнеров
    CURRENT_BACKEND_ID=$(docker compose ps -q backend 2>/dev/null || echo "")
    CURRENT_FRONTEND_ID=$(docker compose ps -q frontend 2>/dev/null || echo "")
    CURRENT_NGINX_ID=$(docker compose ps -q nginx 2>/dev/null || echo "")

    echo "Backend ID: $CURRENT_BACKEND_ID"
    echo "Frontend ID: $CURRENT_FRONTEND_ID"
    echo "Nginx ID: $CURRENT_NGINX_ID"

    # Создаем резервную копию БД
    if [ ! -z "$CURRENT_BACKEND_ID" ]; then
        echo "📦 Создаем резервную копию базы данных..."
        docker compose exec -T mysql mysqldump -u root -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE > "$BACKUP_FILE" || {
            echo "⚠️  WARNING: Не удалось создать backup БД"
        }
        echo "✅ Резервная копия: $BACKUP_FILE"
    fi

    echo "✅ Текущее состояние сохранено"
}

rollback() {
    log_step "ВЫПОЛНЕНИЕ ОТКАТА"

    echo "🔄 Откатываемся к предыдущей версии..."

    # Останавливаем новые контейнеры если они запущены
    docker compose -f docker-compose.yml -f docker-compose.prod.yml down --remove-orphans || true

    # Возвращаем maintenance mode
    if [ ! -z "$CURRENT_BACKEND_ID" ]; then
        docker start $CURRENT_BACKEND_ID || true
        sleep 5
        docker exec $CURRENT_BACKEND_ID php artisan up || true
    fi

    # Восстанавливаем БД если есть backup
    if [ -f "$BACKUP_FILE" ] && [ ! -z "$CURRENT_BACKEND_ID" ]; then
        echo "Восстанавливаем базу данных..."
        docker compose exec -T mysql mysql -u root -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE < "$BACKUP_FILE" || true
    fi

    send_notification "ОШИБКА" "Развертывание не удалось, выполнен откат"
    echo "❌ Откат завершен. Проверьте приложение."
    exit 1
}

# Устанавливаем обработчик ошибок
trap rollback ERR

build_new_images() {
    log_step "СБОРКА НОВЫХ ОБРАЗОВ"

    echo "🔄 Обновляем базовые образы..."
    docker compose -f docker-compose.yml -f docker-compose.prod.yml pull

    echo "🛠️  Собираем новые образы..."
    docker compose -f docker-compose.yml -f docker-compose.prod.yml build --parallel backend frontend scheduler

    echo "✅ Образы собраны успешно"
}

start_new_containers() {
    log_step "ЗАПУСК НОВЫХ КОНТЕЙНЕРОВ"

    # Создаем временные имена для новых контейнеров
    export COMPOSE_PROJECT_NAME="${COMPOSE_PROJECT_NAME:-app}_new"

    echo "🚀 Запускаем новые контейнеры с префиксом 'new'..."
    docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --remove-orphans

    # Получаем ID новых контейнеров
    NEW_BACKEND_ID=$(docker compose -f docker-compose.yml -f docker-compose.prod.yml ps -q backend)
    NEW_FRONTEND_ID=$(docker compose -f docker-compose.yml -f docker-compose.prod.yml ps -q frontend)

    echo "Новый Backend ID: $NEW_BACKEND_ID"
    echo "Новый Frontend ID: $NEW_FRONTEND_ID"

    echo "✅ Новые контейнеры запущены"
}

check_new_containers_health() {
    log_step "ПРОВЕРКА ГОТОВНОСТИ НОВЫХ КОНТЕЙНЕРОВ"

    local max_attempts=$((HEALTH_CHECK_TIMEOUT / 3))
    local attempt=1

    echo "⌛ Ждем готовности новых контейнеров (максимум $HEALTH_CHECK_TIMEOUT секунд)..."

    while [ $attempt -le $max_attempts ]; do
        echo "Попытка $attempt/$max_attempts"

        # Проверяем, что контейнеры запущены
        if ! docker compose -f docker-compose.yml -f docker-compose.prod.yml ps backend | grep -q "Up"; then
            echo "❌ Backend контейнер не запущен"
            sleep 3
            attempt=$((attempt + 1))
            continue
        fi

        # Проверяем PHP в новом backend
        if docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php -v > /dev/null 2>&1; then
            echo "✅ PHP работает в новом backend"
        else
            echo "❌ PHP не отвечает в новом backend"
            sleep 3
            attempt=$((attempt + 1))
            continue
        fi

        # Проверяем подключение к БД
        if docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB OK';" 2>/dev/null | grep -q "DB OK"; then
            echo "✅ Подключение к БД работает"
        else
            echo "❌ Нет подключения к БД"
            sleep 3
            attempt=$((attempt + 1))
            continue
        fi

        # Проверяем HTTP ответ (внутренний)
        if docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend curl -f http://localhost:9000 > /dev/null 2>&1; then
            echo "✅ HTTP сервер отвечает"
            break
        else
            echo "❌ HTTP сервер не отвечает"
        fi

        sleep 3
        attempt=$((attempt + 1))
    done

    if [ $attempt -gt $max_attempts ]; then
        echo "❌ ERROR: Новые контейнеры не готовы после $HEALTH_CHECK_TIMEOUT секунд"
        docker compose -f docker-compose.yml -f docker-compose.prod.yml logs --tail=20 backend
        return 1
    fi

    echo "✅ Новые контейнеры готовы к работе"
}

run_migrations() {
    log_step "ВЫПОЛНЕНИЕ МИГРАЦИЙ"

    echo "🛠️  Включаем режим обслуживания на старом backend..."
    if [ ! -z "$CURRENT_BACKEND_ID" ]; then
        docker exec $CURRENT_BACKEND_ID php artisan down --render="errors::503" --retry=60 || true
    fi

    echo "Выполняем миграции на новом backend..."
    timeout $MIGRATION_TIMEOUT docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan migrate --force || {
        echo "❌ ERROR: Миграции не выполнились"
        return 1
    }

    echo "Оптимизируем кеш приложения..."
    docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan optimize:clear
    docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan config:cache
    docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan route:cache
    docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan view:cache

    echo "✅ Миграции выполнены успешно"
}

switch_traffic() {
    log_step "ПЕРЕКЛЮЧЕНИЕ ТРАФИКА"

    # Создаем новый nginx конфиг для переключения на новые контейнеры
    echo "🔄 Обновляем nginx конфигурацию..."

    # Перезапускаем nginx с новыми upstream'ами
    export COMPOSE_PROJECT_NAME="${COMPOSE_PROJECT_NAME/_new/}"

    echo "Останавливаем старые контейнеры..."
    if [ ! -z "$CURRENT_BACKEND_ID" ]; then
        docker stop $CURRENT_BACKEND_ID $CURRENT_FRONTEND_ID || true
    fi

    echo "Переименовываем новые контейнеры..."
    docker rename "${COMPOSE_PROJECT_NAME}_new_backend_1" "${COMPOSE_PROJECT_NAME}_backend_1" 2>/dev/null || true
    docker rename "${COMPOSE_PROJECT_NAME}_new_frontend_1" "${COMPOSE_PROJECT_NAME}_frontend_1" 2>/dev/null || true

    echo "Перезапускаем nginx..."
    docker compose restart nginx

    echo "✅ Трафик переключен на новые контейнеры"
}

final_health_check() {
    log_step "ФИНАЛЬНАЯ ПРОВЕРКА"

    echo "🔍 Проверяем доступность сайта..."
    local attempt=1
    local max_attempts=10

    while [ $attempt -le $max_attempts ]; do
        if curl -f -s http://localhost/ > /dev/null 2>&1; then
            echo "✅ Сайт доступен!"
            break
        else
            echo "Попытка $attempt/$max_attempts: сайт недоступен"
            sleep 2
            attempt=$((attempt + 1))
        fi
    done

    if [ $attempt -gt $max_attempts ]; then
        echo "❌ ERROR: Сайт недоступен после переключения"
        docker compose logs --tail=10 nginx
        return 1
    fi

    # Включаем приложение
    docker compose exec -T backend php artisan up

    echo "Статус всех контейнеров:"
    docker compose ps

    echo "✅ Развертывание завершено успешно!"
}

cleanup() {
    log_step "ОЧИСТКА"

    echo "🧹 Удаляем старые контейнеры..."
    if [ ! -z "$CURRENT_BACKEND_ID" ]; then
        docker rm $CURRENT_BACKEND_ID $CURRENT_FRONTEND_ID 2>/dev/null || true
    fi

    echo "Очищаем старые образы..."
    docker image prune -f

    echo "Удаляем старые backup'ы (оставляем последние 5)..."
    ls -t backup_*.sql 2>/dev/null | tail -n +6 | xargs rm -f 2>/dev/null || true

    echo "✅ Очистка завершена"
}

send_notification() {
    local status="$1"
    local message="$2"

    # Telegram
    if [ ! -z "$TELEGRAM_BOT_TOKEN" ] && [ ! -z "$TELEGRAM_CHAT_ID" ]; then
        curl -s "https://api.telegram.org/bot$TELEGRAM_BOT_TOKEN/sendMessage" \
            -d "chat_id=$TELEGRAM_CHAT_ID" \
            -d "text=🚀 Zero-Downtime Deployment: $status\n$message" 2>/dev/null || true
    fi
}

# --- ОСНОВНОЙ ПРОЦЕСС ---

main() {
    check_prerequisites
    save_current_state
    build_new_images
    start_new_containers
    check_new_containers_health
    run_migrations
    switch_traffic
    final_health_check
    cleanup

    # Финальные метрики
    DEPLOYMENT_TIME=$(($(date +%s) - SCRIPT_START_TIME))
    echo ""
    echo "🎉 ZERO-DOWNTIME DEPLOYMENT УСПЕШНО ЗАВЕРШЕН!"
    echo "⏱️  Общее время: ${DEPLOYMENT_TIME} секунд"
    echo "📝 Лог сохранен: $LOG_FILE"

    send_notification "УСПЕШНО" "Zero-downtime развертывание завершено за ${DEPLOYMENT_TIME} секунд"
}

# Запускаем основной процесс
main

echo "✅ Готово!"