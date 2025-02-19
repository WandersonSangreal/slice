FROM php:8.2-zts

WORKDIR /app

COPY . /app

RUN apt-get update && apt-get install -y libpq-dev libuv1-dev libssl-dev libnghttp2-dev libbrotli-dev libcurl4-openssl-dev unzip git \
	&& docker-php-ext-install pdo_pgsql pgsql sockets \
    && docker-php-source extract \
    && pecl install swoole \
    && docker-php-ext-enable swoole \
    && docker-php-source delete \
    && apt-get clean

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN composer install --optimize-autoloader

VOLUME [ "app/files" ]

# CMD ["php", "process.php"]
