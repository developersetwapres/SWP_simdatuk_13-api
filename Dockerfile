
FROM alpine:3.22

LABEL Maintainer="Pusat Pengembangan dan Layanan Sistem Informasi <setnegapps@setneg.go.id>"

WORKDIR /var/www

# =========================================================
# 1. SYSTEM DEPENDENCIES
# =========================================================
RUN apk add --no-cache \
    curl \
    nginx \
    nodejs \
    npm \
    php84 \
    php84-bcmath \
    php84-ctype \
    php84-curl \
    php84-dom \
    php84-fileinfo \
    php84-fpm \
    php84-gd \
    php84-iconv \
    php84-intl \
    php84-ldap \
    php84-mbstring \
    php84-mysqli \
    php84-opcache \
    php84-openssl \
    php84-pdo \
    php84-pdo_mysql \
    php84-pdo_pgsql \
    php84-pgsql \
    php84-pecl-imagick \
    php84-phar \
    php84-session \
    php84-simplexml \
    php84-tokenizer \
    php84-xml \
    php84-xmlreader \
    php84-xmlwriter \
    php84-zip \
    supervisor \
    tzdata \
    postgresql-client

# =========================================================
# 2. PHP AND TIMEZONE
# =========================================================
RUN ln -sf /usr/bin/php84 /usr/bin/php

ENV TZ=Asia/Jakarta

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# =========================================================
# 3. APPLICATION SOURCE
# =========================================================
COPY src/ /var/www/

# =========================================================
# 4. WEB SERVER AND SUPERVISOR CONFIGURATION
# =========================================================
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/local.conf /etc/nginx/conf.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# =========================================================
# 5. INSTALL LARAVEL DEPENDENCIES
# =========================================================
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist

# =========================================================
# 6. BUILD FRONTEND
# =========================================================
RUN npm install --legacy-peer-deps \
    && npm run build \
    && rm -f public/hot

# =========================================================
# 7. LARAVEL STORAGE
# =========================================================
RUN php artisan storage:link

# =========================================================
# 8. NON-ROOT USER
# =========================================================
RUN addgroup -g 1945 -S pplsi \
    && adduser -u 1945 -S pplsi -G pplsi

# =========================================================
# 9. PHP-FPM CONFIGURATION
# =========================================================

# Align PHP-FPM pool with container user.
# Keep PHP-FPM listening on TCP port 9000.
RUN sed -i \
    -e 's/^user = nobody/user = pplsi/' \
    -e 's/^group = nobody/group = pplsi/' \
    /etc/php84/php-fpm.d/www.conf

# =========================================================
# 10. RUNTIME DIRECTORIES AND PERMISSIONS
# =========================================================
RUN mkdir -p \
    /run/nginx \
    /var/log/nginx \
    /var/log/php84 \
    /var/www/storage/logs \
    /var/www/storage/framework/cache/data \
    /var/www/storage/framework/sessions \
    /var/www/storage/framework/views \
    /var/www/bootstrap/cache \
    && chown -R pplsi:pplsi \
        /var/www \
        /run \
        /var/lib/nginx \
        /var/log/nginx \
        /var/log/php84 \
    && chmod -R u+rwX \
        /var/www/storage \
        /var/www/bootstrap/cache \
    && chmod 755 /var/log/php84

# =========================================================
# 11. VALIDATE CONFIGURATION DURING BUILD
# =========================================================
RUN php-fpm84 -t \
    && nginx -t

# =========================================================
# 12. RUN AS NON-ROOT USER
# =========================================================
USER pplsi

# =========================================================
# 13. EXPOSE WEB PORT
# =========================================================
EXPOSE 8080

# =========================================================
# 14. HEALTHCHECK
# =========================================================
HEALTHCHECK --interval=30s \
    --timeout=10s \
    --start-period=30s \
    --retries=3 \
    CMD curl --silent --fail http://127.0.0.1:8080/up || exit 1

# =========================================================
# 15. START SERVICES
# =========================================================
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
