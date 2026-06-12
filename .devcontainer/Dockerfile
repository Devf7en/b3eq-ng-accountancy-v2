FROM php:8.2-apache

ARG DOLIBARR_VERSION=15.0.0
ARG NODE_VERSION=20

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        unzip \
        wget \
        git \
        curl \
        gnupg2 \
        lsb-release \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libwebp-dev \
        libfreetype6-dev \
        libonig-dev \
        libxml2-dev \
        libicu-dev \
        zlib1g-dev \
    && curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | bash - \
    && apt-get install -y nodejs \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install mysqli pdo pdo_mysql zip gd intl opcache \
    && a2enmod rewrite headers \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
RUN mkdir -p /var/www/html/custom \
    && wget -qO /tmp/dolibarr.zip https://github.com/Dolibarr/dolibarr/releases/download/${DOLIBARR_VERSION}/dolibarr-${DOLIBARR_VERSION}.zip \
    && unzip /tmp/dolibarr.zip -d /tmp \
    && mv /tmp/dolibarr-${DOLIBARR_VERSION}/* /var/www/html/ \
    && rm -rf /tmp/dolibarr.zip /tmp/dolibarr-${DOLIBARR_VERSION} \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html
