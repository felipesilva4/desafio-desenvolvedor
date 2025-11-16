FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git curl zip unzip libpq-dev libonig-dev libxml2-dev libzip-dev \
    pkg-config libssl-dev libsasl2-dev \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev libwebp-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip bcmath gd \
    && pecl install mongodb pcov \
    && docker-php-ext-enable mongodb pcov

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
# aumentar o tamanho máximo de upload
RUN echo "upload_max_filesize = 200M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 200M" >> /usr/local/etc/php/conf.d/uploads.ini
    
COPY php.ini /usr/local/etc/php/conf.d/custom-php.ini


WORKDIR /var/www/html

RUN chown -R www-data:www-data /var/www
