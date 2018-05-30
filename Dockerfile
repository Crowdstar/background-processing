FROM php:7.2-fpm

RUN \
    apt-get update                && \
    apt-get install -y zlib1g-dev && \
    docker-php-ext-install zip    && \
    yes '' | pecl install apcu    && \
    docker-php-ext-enable apcu    && \
    curl                     \
        -sf                  \
        --connect-timeout 5  \
        --max-time        15 \
        --retry           5  \
        --retry-delay     2  \
        --retry-max-time  60 \
        http://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

COPY . /var/www/html

RUN composer update -n
