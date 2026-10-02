
FROM php:8.4-fpm

WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    bash \
    libzip-dev \
    unzip \
    git \
    curl \
    nginx \
    supervisor \
    vim \
    && rm -rf /var/lib/apt/lists/*

RUN curl -sSL https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions \
    -o - | sh -s \
    ctype \
    curl \
    dom \
    fileinfo \
    filter \
    hash \
    mbstring \
    openssl \
    pcre \
    pdo \
    pdo_mysql \
    session \
    tokenizer \
    xml \
    gd \
    zip \
    @composer

# Laravel source
COPY ./src/ /var/www/

# Docker configuration
COPY ./config/nginx.conf /etc/nginx/nginx.conf
COPY ./config/fpm-pool.conf /usr/local/etc/php-fpm.d/www.conf
COPY ./config/php.ini /usr/local/etc/php/conf.d/99-custom.ini
COPY ./config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Install dependencies
RUN composer install --no-scripts --no-autoloader

RUN composer clear-cache \
    && composer dump-autoload --no-scripts --optimize

# Laravel storage link
RUN php artisan storage:link

# Permissions
RUN chown -R www-data:www-data /var/www

CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

EXPOSE 8080

HEALTHCHECK --interval=300s --timeout=60s \
    CMD curl -f http://127.0.0.1:8080/api || exit 1