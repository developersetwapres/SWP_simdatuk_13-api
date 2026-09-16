FROM alpine
LABEL Maintainer="Pusat Pengembangan dan Layanan Sistem Informasi <setnegapps@setneg.go.id>"

WORKDIR /var/www

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

RUN ln -sf /usr/bin/php84 /usr/bin/php

COPY --from=composer/composer:latest-bin /composer /usr/bin/composer

ENV TZ Asia/Jakarta

#RUN mkdir /data/simdatukswp

COPY ./config/nginx.conf /etc/nginx/nginx.conf
COPY ./config/fpm-pool.conf /etc/php84/php-fpm.d/www.conf
COPY ./config/php.ini /etc/php84/conf.d/custom.ini
COPY ./config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

COPY src/ /var/www

COPY ./config/.env /var/www

# Copy images from public/images to storage/app/public/images
#RUN mkdir -p storage/app/public/images 

RUN ln -s /data/simdatukswp/images /var/www/storage/app/public/images

RUN composer install --optimize-autoloader --no-dev
# RUN composer install 
RUN php artisan storage:link

RUN npm install --legacy-peer-deps
RUN npm run build && rm -f public/hot

RUN addgroup -g 1945 -S pplsi && adduser -u 1945 -S pplsi -G pplsi
RUN chown -R pplsi:pplsi /var/www /run /var/lib/nginx /var/log/nginx
USER pplsi

EXPOSE 8080

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

HEALTHCHECK --timeout=10s CMD curl --silent --fail http://127.0.0.1:8080/fpm-ping
