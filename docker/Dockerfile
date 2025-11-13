FROM php:8.2-fpm
WORKDIR /usr/local/src/car-rental-project

RUN apt-get update && \
    apt-get install -y --no-install-recommends \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libpq-dev \
    libzip-dev && \
    apt-get install -y \
    git \
    unzip \
    zip \
    sqlite3 \
    libcurl4-openssl-dev \
    pkg-config \
    libssl-dev \
    openssh-client \
    default-mysql-client && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

RUN chown -R www-data:www-data .
RUN docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg
RUN docker-php-ext-install pdo pdo_mysql sockets bcmath zip pcntl posix pgsql pdo_pgsql exif gd
RUN pecl install mongodb-1.15.0
RUN pecl install redis
RUN pecl install xdebug
RUN docker-php-ext-enable xdebug
COPY . .
RUN chmod +x ./wait-for-it.sh
COPY ./docker/dev.ini /usr/local/etc/php/conf.d/dev.ini

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN git config --global --add safe.directory /usr/local/src/car-rental-project
RUN composer install
RUN chown -R www-data:www-data .
USER root
