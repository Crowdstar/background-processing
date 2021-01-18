FROM php:%%PHP_VERSION%%-fpm

COPY . /var/www/html

RUN \
    apt-get update     && \
    apt-get install -y    \
        zlib1g-dev        \
        libzip-dev     && \
    docker-php-ext-install zip && \
    yes '' | pecl install apcu && \
    docker-php-ext-enable apcu && \
    curl -sfL http://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer && \
    composer update -nq --no-progress
