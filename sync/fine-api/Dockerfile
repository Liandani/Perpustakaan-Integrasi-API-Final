FROM php:8.4-cli

WORKDIR /app

RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl \
    libzip-dev \
    zip

RUN docker-php-ext-install pdo pdo_mysql sockets

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY ./sync/fine-api .
COPY ./packages /packages

RUN composer update && cp .env.example .env && php artisan key:generate

EXPOSE 8000

CMD php artisan serve --host=0.0.0.0 --port=8000
