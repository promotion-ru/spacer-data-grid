# Development Dockerfile для Laravel приложения
FROM php:8.4-fpm

# Аргумент для определения окружения
ARG APP_ENV=development
ENV APP_ENV=${APP_ENV}

# Установка системных зависимостей
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    libicu-dev \
    libzip-dev \
    zip \
    unzip \
    nodejs \
    npm \
    # Утилиты для development
    vim \
    htop \
    procps \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Установка PHP расширений
RUN docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    opcache \
    intl \
    zip

# Установка Redis расширения
RUN pecl install redis \
    && docker-php-ext-enable redis

# Скачиваем health check скрипт для PHP-FPM
RUN curl -o /usr/local/bin/php-fpm-healthcheck \
    https://raw.githubusercontent.com/renatomefi/php-fpm-healthcheck/master/php-fpm-healthcheck \
    && chmod +x /usr/local/bin/php-fpm-healthcheck

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Копируем конфигурационные файлы PHP
COPY ./docker/php-fpm/php-custom.ini /usr/local/etc/php/conf.d/php-custom.ini
COPY ./docker/php-fpm/opcache-development.ini /usr/local/etc/php/conf.d/opcache.ini
COPY ./docker/php-fpm/php-fpm-pool.conf /usr/local/etc/php-fpm.d/www.conf

# Копируем entrypoint скрипт
COPY ./docker/php-fpm/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Создание пользователя www-data (если не существует)
RUN if ! id -u www-data > /dev/null 2>&1; then \
        groupadd -g 1000 www-data && \
        useradd -u 1000 -ms /bin/bash -g www-data www-data; \
    fi

# Установка рабочей директории
WORKDIR /var/www

# Копируем файлы приложения
COPY --chown=www-data:www-data . /var/www

# Создаем необходимые директории и устанавливаем права
RUN mkdir -p /var/www/storage/{app,framework/{sessions,views,cache/data},logs} \
    && mkdir -p /var/www/bootstrap/cache \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Установка зависимостей Composer (только в development)
RUN composer install --optimize-autoloader

# Генерация автозагрузчика
RUN composer dump-autoload --optimize

# Entrypoint для инициализации
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Переключаемся на пользователя www-data
USER www-data

# Экспонирование порта 9000 для PHP-FPM
EXPOSE 9000

# Команда по умолчанию - запуск PHP-FPM
CMD ["php-fpm"]
