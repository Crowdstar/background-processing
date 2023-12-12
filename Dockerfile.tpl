FROM php:%%PHP_VERSION%%-fpm

COPY . /var/www/html
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/

RUN \
    apt-get update     && \
    apt-get install -y    \
        zlib1g-dev        \
        libzip-dev     && \
    docker-php-ext-install zip && \
    yes '' | pecl install apcu && \
    docker-php-ext-enable apcu && \
    composer update -nq --no-progress
