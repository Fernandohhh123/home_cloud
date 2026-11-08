FROM php:8.2-fpm

# 1. Instalar las dependencias del sistema operativo Debian
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    pkg-config \
	tree \
	nano

# 2. Instalar las extensiones de PHP usando el script oficial
RUN docker-php-ext-install pdo pdo_sqlite

RUN groupadd -g 48 cloudfiles \
    && usermod -aG cloudfiles www-data

WORKDIR /var/www/html
