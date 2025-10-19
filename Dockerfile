FROM php:8.2-fpm-alpine

# Installer les dépendances système pour les extensions PHP
RUN apk update && apk add --no-cache \
    git \
    curl \
    unzip \
    zip \
    libxml2-dev \
    libsodium-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    postgresql-dev \
    mysql-client \
    mysql-dev \
    libzip-dev \
    && rm -rf /var/cache/apk/*

# Configuration et installation des extensions PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_pgsql \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    sodium

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Installation de Node.js et npm
RUN apk add --no-cache nodejs npm

WORKDIR /var/www/html

COPY . .

EXPOSE 8000

# Exécuter les commandes d'installation
RUN composer install --no-dev --optimize-autoloader
RUN npm install

# Commande de démarrage finale
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000