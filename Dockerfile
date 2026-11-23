
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
# 2. PHP, COMPOSER, AND TIMEZONE
# =========================================================
RUN ln -sf /usr/bin/php84 /usr/bin/php

ENV TZ=Asia/Jakarta

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Load custom PHP configuration
COPY docker/php.ini /etc/php84/conf.d/99-custom.ini


# =========================================================
# 3. APPLICATION SOURCE
# =========================================================
COPY src/ /var/www/


# Copy konfigurasi dari repository config Setneg
COPY config-simdatuk/.env /var/www/.env


# =========================================================
# 4. NGINX AND SUPERVISOR CONFIGURATION
# =========================================================
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/local.conf /etc/nginx/conf.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# =========================================================
# 5. COMPOSER DEPENDENCIES
# =========================================================
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist

# =========================================================
# 6. FRONTEND BUILD
# =========================================================
RUN npm install --legacy-peer-deps \
    && npm run build \
    && rm -f public/hot

# =========================================================
# 7. LARAVEL STORAGE
# =========================================================
RUN php artisan storage:link

# =========================================================
# 8. CREATE NON-ROOT USER
# =========================================================
RUN addgroup -g 1945 -S pplsi \
    && adduser -u 1945 -S pplsi -G pplsi

# =========================================================
# 9. PHP-FPM POOL CONFIGURATION
# =========================================================

# Align PHP-FPM pool with the container user.
RUN sed -i \
    -e 's/^user = nobody/user = pplsi/' \
    -e 's/^group = nobody/group = pplsi/' \
    /etc/php84/php-fpm.d/www.conf

# PHP-FPM listens on the same FastCGI port as Nginx.
RUN sed -i \
    -E 's@^[[:space:]]*listen[[:space:]]*=.*@listen = 127.0.0.1:9000@' \
    /etc/php84/php-fpm.d/www.conf

# =========================================================
# 10. PHP-FPM LOGGING TO STDERR
# =========================================================

# Remove existing global error_log directives, then
# configure the PHP-FPM master to log to stderr.
RUN sed -i -E \
    '/^[[:space:]]*;?[[:space:]]*error_log[[:space:]]*=/d' \
    /etc/php84/php-fpm.conf \
    && sed -i \
    '/^\[global\]/a error_log = /dev/stderr' \
    /etc/php84/php-fpm.conf

# Capture PHP-FPM worker stdout and stderr.
RUN sed -i \
    '/^\[www\]/a catch_workers_output = yes\ndecorate_workers_output = no' \
    /etc/php84/php-fpm.d/www.conf

# Route PHP runtime errors from workers to stderr.
RUN printf '\nphp_admin_flag[log_errors] = on\nphp_admin_value[error_log] = /proc/self/fd/2\n' \
    >> /etc/php84/php-fpm.d/www.conf

# =========================================================
# 11. NGINX LOGGING
# =========================================================

# Existing nginx.conf uses:
# access_log /dev/stdout;
# error_log /dev/stderr;
#
# Nginx is started with -e /dev/stderr by Supervisor,
# including early startup errors.

# =========================================================
# 12. RUNTIME DIRECTORIES
# =========================================================
RUN mkdir -p \
    /run/nginx \
    /var/lib/nginx \
    /var/log/nginx \
    /var/www/storage/logs \
    /var/www/storage/framework/cache/data \
    /var/www/storage/framework/sessions \
    /var/www/storage/framework/views \
    /var/www/bootstrap/cache

# =========================================================
# 13. FILE OWNERSHIP AND PERMISSIONS
# =========================================================
RUN chown -R pplsi:pplsi \
        /var/www \
        /run \
        /var/lib/nginx \
        /var/log/nginx \
    && chmod -R u+rwX \
        /var/www/storage \
        /var/www/bootstrap/cache

# =========================================================
# 14. RUN AS NON-ROOT
# =========================================================
USER pplsi

# =========================================================
# 15. VALIDATE AS RUNTIME USER
# =========================================================

# Validate using pplsi, not root.
# This prevents root-owned logs from being generated
# after the ownership configuration.
RUN php-fpm84 -t \
    && nginx -e /dev/stderr -t

# =========================================================
# 16. APPLICATION PORT
# =========================================================
EXPOSE 8080

# =========================================================
# 17. CONTAINER HEALTHCHECK
# =========================================================
HEALTHCHECK --interval=30s \
    --timeout=10s \
    --start-period=30s \
    --retries=3 \
    CMD curl --silent --fail http://127.0.0.1:8080/up || exit 1

# =========================================================
# 18. START SUPERVISOR
# =========================================================
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
