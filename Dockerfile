# E-Novaz Docker Image
# PHP 8.2 + Nginx + Supervisor untuk Laravel 12 (versi final)

FROM php:8.2-fpm-alpine

# Paket sistem + ekstensi PHP yang dibutuhkan aplikasi versi final
# (sodium, mbstring, pdo_sqlite sudah ada di image resmi PHP)
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    nodejs \
    npm \
    mariadb-client \
    supervisor \
    nginx \
    freetype-dev \
    libjpeg-turbo-dev

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql zip exif pcntl bcmath gd

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Konfigurasi Nginx & Supervisor
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisor/supervisord.conf /etc/supervisord.conf

RUN mkdir -p /run/nginx /var/log/nginx

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
