FROM php:8.3-fpm-bookworm

# Installer les extensions nécessaires à l'application
RUN docker-php-ext-install pdo_mysql \
    && apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev \
    && docker-php-ext-install mbstring \
    && apt-get purge -y --auto-remove libonig-dev \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

EXPOSE 9000
