FROM php:8.2-apache

# =========================
# SISTEMA BASE
# =========================
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    curl \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libcurl4-openssl-dev \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# =========================
# EXTENSIONES PHP IMPORTANTES
# =========================
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mysqli \
    mbstring \
    zip \
    exif \
    pcntl \
    bcmath \
    intl \
    curl

# =========================
# APACHE CONFIG
# =========================
RUN a2enmod rewrite

# =========================
# COMPOSER
# =========================
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# =========================
# PROYECTO
# =========================
WORKDIR /var/www/html
COPY . .

# Permisos (IMPORTANTE en Render)
RUN chown -R www-data:www-data /var/www/html

# =========================
# PUERTO
# =========================
EXPOSE 80
