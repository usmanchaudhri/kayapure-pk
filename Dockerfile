FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libpng-dev libjpeg62-turbo-dev libfreetype6-dev libzip-dev libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" mysqli gd exif zip intl opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

RUN printf '%s\n' \
        '<Directory /var/www/html>' \
        '    AllowOverride All' \
        '    Require all granted' \
        '</Directory>' \
        > /etc/apache2/conf-available/wordpress.conf \
    && a2enconf wordpress \
    && printf '%s\n' \
        'memory_limit=512M' \
        'upload_max_filesize=64M' \
        'post_max_size=64M' \
        'max_execution_time=120' \
        > /usr/local/etc/php/conf.d/wordpress.ini

WORKDIR /var/www/html
