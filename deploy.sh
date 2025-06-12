#!/bin/bash

# Включаем строгий режим для отлова ошибок
set -euo pipefail

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Функция для цветного вывода
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Загрузка переменных окружения
set -a
source .env
set +a

# Проверка переменных
if [ -z "$MYSQL_ROOT_PASSWORD" ] || [ -z "$MYSQL_PASSWORD" ]; then
    log_error "MYSQL_ROOT_PASSWORD and MYSQL_PASSWORD must be set in .env"
    exit 1
fi

# Определяем команду Docker Compose (поддержка обеих версий)
if command -v "docker compose" &> /dev/null; then
    DOCKER_COMPOSE="docker compose"
    log_info "Используется Docker Compose V2"
elif command -v "docker-compose" &> /dev/null; then
    DOCKER_COMPOSE="docker-compose"
    log_warning "Используется старая версия docker-compose. Рекомендуется обновить до V2"
else
    log_error "Docker Compose не найден!"
    exit 1
fi

# Определяем имя проекта для правильных имён контейнеров
PROJECT_NAME=$(basename "$PWD" | tr '[:upper:]' '[:lower:]' | sed 's/[^a-z0-9]//g')
MYSQL_CONTAINER="${PROJECT_NAME}_mysql"

# Функция проверки здоровья контейнера
check_container_health() {
    local container_name=$1
    local max_attempts=${2:-30}
    local attempt=0

    while [ $attempt -lt $max_attempts ]; do
        if docker inspect "$container_name" >/dev/null 2>&1; then
            local health_status=$(docker inspect "$container_name" --format='{{.State.Health.Status}}' 2>/dev/null || echo "none")
            local running_status=$(docker inspect "$container_name" --format='{{.State.Running}}' 2>/dev/null || echo "false")

            if [ "$health_status" = "healthy" ] || ([ "$health_status" = "none" ] && [ "$running_status" = "true" ]); then
                return 0
            fi
        fi

        attempt=$((attempt + 1))
        sleep 2
    done

    return 1
}

# Проверка состояния MySQL контейнера
log_info "Проверяем состояние MySQL контейнера..."
if ! docker inspect "$MYSQL_CONTAINER" >/dev/null 2>&1; then
    log_warning "MySQL контейнер не найден, создаём..."
    $DOCKER_COMPOSE -f docker-compose.yml -f docker-compose.prod.yml up -d mysql
    sleep 30
else
    # Проверяем метаданные контейнера
    if ! docker inspect "$MYSQL_CONTAINER" | jq -e '.[0].Config' >/dev/null 2>&1; then
        log_warning "Обнаружены повреждённые метаданные MySQL контейнера"
        log_info "Создаём резервную копию БД..."

        # Попытка создать бэкап
        docker exec "$MYSQL_CONTAINER" mysqldump -u root -p"${MYSQL_ROOT_PASSWORD}" --all-databases > "mysql_backup_emergency_$(date +%Y%m%d_%H%M%S).sql" 2>/dev/null || {
            log_warning "Не удалось создать резервную копию БД"
        }

        log_info "Пересоздаём контейнер MySQL..."
        $DOCKER_COMPOSE -f docker-compose.yml -f docker-compose.prod.yml stop mysql
        docker rm -f "$MYSQL_CONTAINER"
        $DOCKER_COMPOSE -f docker-compose.yml -f docker-compose.prod.yml up -d mysql

        log_info "Ждём инициализации MySQL..."
        sleep 45
    fi
fi

# Проверка состояния MySQL тома
MYSQL_VOLUME="${PROJECT_NAME}_mysql_prod_data"
if ! docker volume inspect "$MYSQL_VOLUME" >/dev/null 2>&1; then
    log_info "Создаём новый MySQL том..."
    docker volume create "$MYSQL_VOLUME"
fi

log_info "Начинаем развертывание с минимальным downtime..."

# 1. Сборка новых образов БЕЗ остановки контейнеров
log_info "Собираем новые образы..."
$DOCKER_COMPOSE -f docker-compose.yml -f docker-compose.prod.yml build --parallel backend frontend || {
    log_error "Ошибка при сборке образов"
    exit 1
}

# 2. Проверяем, что критические сервисы (MySQL, Redis) запущены
log_info "Проверяем критические сервисы..."
$DOCKER_COMPOSE -f docker-compose.yml -f docker-compose.prod.yml up -d mysql redis

# Ждём готовности MySQL
log_info "Ожидаем готовности MySQL..."
if ! check_container_health "${PROJECT_NAME}_mysql" 60; then
    log_error "MySQL не готов после 120 секунд ожидания"
    $DOCKER_COMPOSE -f docker-compose.yml -f docker-compose.prod.yml logs --tail=50 mysql
    exit 1
fi

# 3. Быстрая замена контейнеров приложения
log_info "Быстро заменяем контейнеры приложения..."

# Останавливаем только приложение
$DOCKER_COMPOSE -f docker-compose.yml -f docker-compose.prod.yml stop frontend backend scheduler

# Удаляем старые контейнеры приложения
$DOCKER_COMPOSE -f docker-compose.yml -f docker-compose.prod.yml rm -f frontend backend scheduler

# Запускаем новые контейнеры
$DOCKER_COMPOSE -f docker-compose.yml -f docker-compose.prod.yml up -d backend frontend scheduler

# 4. Ждем готовности контейнеров
log_info "Ожидаем готовности контейнеров..."
sleep 10

# Проверяем готовность backend
log_info "Проверяем backend..."
BACKEND_READY=false
for i in {1..30}; do
    if $DOCKER_COMPOSE -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php -v > /dev/null 2>&1; then
        BACKEND_READY=true
        break
    fi
    echo -n "."
    sleep 2
done
echo

if [ "$BACKEND_READY" = false ]; then
    log_error "Backend не готов"
    $DOCKER_COMPOSE -f docker-compose.yml -f docker-compose.prod.yml logs --tail=50 backend
    exit 1
fi

# Проверяем готовность frontend
log_info "Проверяем frontend..."
FRONTEND_READY=false
for i in {1..30}; do
    if $DOCKER_COMPOSE -f docker-compose.yml -f docker-compose.prod.yml logs frontend 2>/dev/null | grep -q "Listening on http://0.0.0.0:3000"; then
        FRONTEND_READY=true
        break
    fi
    echo -n "."
    sleep 2
done
echo

if [ "$FRONTEND_READY" = false ]; then
    log_error "Frontend не готов"
    $DOCKER_COMPOSE -f docker-compose.yml -f docker-compose.prod.yml logs --tail=50 frontend
    exit 1
fi

# 5. Перезапускаем nginx для обновления DNS кеша
log_info "Перезапускаем nginx для обновления DNS кеша..."
$DOCKER_COMPOSE -f docker-compose.yml -f docker-compose.prod.yml restart nginx

# Даем nginx время на старт
sleep 5

# 6. Выполняем миграции
log_info "Выполняем миграции базы данных..."
if ! $DOCKER_COMPOSE -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan migrate --force; then
    log_error "Ошибка при выполнении миграций"
    exit 1
fi

# 7. Очистка и оптимизация кеша Laravel
log_info "Оптимизируем кеш Laravel..."
$DOCKER_COMPOSE -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan cache:clear
$DOCKER_COMPOSE -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan config:cache
$DOCKER_COMPOSE -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan route:cache
$DOCKER_COMPOSE -f docker-compose.yml -f docker-compose.prod.yml exec -T backend php artisan view:cache

# Опционально: оптимизация автозагрузки Composer
$DOCKER_COMPOSE -f docker-compose.yml -f docker-compose.prod.yml exec -T backend composer dump-autoload --optimize --no-dev 2>/dev/null || true

# 8. Финальная проверка доступности
log_info "Выполняем финальную проверку доступности..."
SITE_AVAILABLE=false
for i in {1..15}; do
    if curl -f -s -o /dev/null -w "%{http_code}" http://localhost/ | grep -q "200\|301\|302"; then
        SITE_AVAILABLE=true
        break
    fi
    echo -n "."
    sleep 2
done
echo

if [ "$SITE_AVAILABLE" = true ]; then
    log_info "✅ Развертывание завершено успешно!"
    log_info "Сайт доступен!"

    # Показываем статус всех контейнеров
    echo
    log_info "Статус контейнеров:"
    $DOCKER_COMPOSE -f docker-compose.yml -f docker-compose.prod.yml ps
else
    log_error "❌ Сайт недоступен после развертывания!"
    log_info "Логи nginx:"
    $DOCKER_COMPOSE -f docker-compose.yml -f docker-compose.prod.yml logs --tail=20 nginx
    log_info "Логи backend:"
    $DOCKER_COMPOSE -f docker-compose.yml -f docker-compose.prod.yml logs --tail=20 backend
    exit 1
fi

# 9. Очистка старых образов
log_info "Очищаем старые Docker образы..."
docker image prune -f

# 10. Показываем использование дискового пространства
log_info "Использование дискового пространства Docker:"
docker system df

echo
log_info "✅ Развертывание завершено успешно!"
log_info "Время выполнения: $SECONDS секунд"