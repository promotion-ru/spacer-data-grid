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

domains=(grid.lk.cool)
rsa_key_size=4096
data_path="./certbot"
email="ru.promotion@yandex.ru"
staging=0 # Поставьте 1 для тестирования, 0 для production

# Определяем аргумент staging
staging_arg=""
if [ $staging != "0" ]; then
    staging_arg="--staging"
    echo "### ВНИМАНИЕ: Используется STAGING режим (тестовые сертификаты) ###"
else
    echo "### Используется PRODUCTION режим (реальные сертификаты) ###"
fi

if [ -d "$data_path" ]; then
  read -p "Existing data found for $domains. Continue and replace existing certificate? (y/N) " decision
  if [ "$decision" != "Y" ] && [ "$decision" != "y" ]; then
    exit
  fi
fi

if [ ! -e "$data_path/conf/options-ssl-nginx.conf" ] || [ ! -e "$data_path/conf/ssl-dhparams.pem" ]; then
  echo "### Downloading recommended TLS parameters ..."
  mkdir -p "$data_path/conf"
  curl -s https://raw.githubusercontent.com/certbot/certbot/master/certbot-nginx/certbot_nginx/_internal/tls_configs/options-ssl-nginx.conf > "$data_path/conf/options-ssl-nginx.conf"
  curl -s https://raw.githubusercontent.com/certbot/certbot/master/certbot/certbot/ssl-dhparams.pem > "$data_path/conf/ssl-dhparams.pem"
  echo
fi

echo "### Creating dummy certificate for $domains ..."
path="/etc/letsencrypt/live/$domains"
mkdir -p "$data_path/conf/live/$domains"
docker-compose -f docker-compose.yml -f docker-compose.prod.yml run --rm --entrypoint "\
  openssl req -x509 -nodes -newkey rsa:$rsa_key_size -days 1\
    -keyout '$path/privkey.pem' \
    -out '$path/fullchain.pem' \
    -subj '/CN=localhost'" certbot
echo

echo "### Starting nginx ..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up --force-recreate -d nginx
echo

echo "### Deleting dummy certificate for $domains ..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml run --rm --entrypoint "\
  rm -Rf /etc/letsencrypt/live/$domains && \
  rm -Rf /etc/letsencrypt/archive/$domains && \
  rm -Rf /etc/letsencrypt/renewal/$domains.conf" certbot
echo

echo "### Requesting Let's Encrypt certificate for $domains ..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml run --rm --entrypoint "\
  certbot certonly --webroot -w /var/www/certbot \
    --email $email \
    --agree-tos \
    --no-eff-email \
    $staging_arg \
    -d $domains" certbot

if [ $? -eq 0 ]; then
    echo "### Сертификат успешно получен! ###"
else
    echo "### ОШИБКА при получении сертификата! ###"
    echo "Проверьте:"
    echo "1. DNS настройки для $domains"
    echo "2. Доступность порта 80 извне"
    echo "3. Логи certbot для деталей"
    exit 1
fi

echo

echo "### Reloading nginx ..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml exec nginx nginx -s reload

if [ $? -eq 0 ]; then
    echo "### Nginx успешно перезагружен! ###"
    if [ $staging == "0" ]; then
        echo "### SSL сертификат установлен! Сайт доступен по https://$domains ###"
    else
        echo "### Staging сертификат установлен! Для production измените staging=0 ###"
    fi
else
    echo "### ОШИБКА при перезагрузке nginx! ###"
    docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs nginx
    exit 1
fi

# Проверяем статус сертификата
echo "### Проверка статуса сертификата ..."
docker-compose -f docker-compose.yml -f docker-compose.prod.yml run --rm --entrypoint "\
  certbot certificates" certbot

echo "### Готово! ###"