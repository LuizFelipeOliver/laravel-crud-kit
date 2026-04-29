FROM php:8.5-cli

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libonig-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install mbstring exif

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www