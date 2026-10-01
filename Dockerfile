FROM php:8.4-apache

# System dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libwebp-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libcurl4-openssl-dev \
    && rm -rf /var/lib/apt/lists/*

# GD
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp

# PHP extensions commonly needed by Drupal
RUN docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mysqli \
    gd \
    intl \
    mbstring \
    curl \
    zip \
    opcache

# Apache modules used by Drupal
RUN a2enmod rewrite headers expires

# Drupal recommended-project uses /web
ENV APACHE_DOCUMENT_ROOT=/var/www/html/web

RUN sed -ri \
    -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" \
    /etc/apache2/sites-available/*.conf

# Allow Drupal .htaccess
RUN printf '<Directory /var/www/html/web>\n\
    Options FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n' \
    > /etc/apache2/conf-available/drupal.conf \
    && a2enconf drupal

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html