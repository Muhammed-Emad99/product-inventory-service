FROM php:8.2-fpm-alpine

# Set working directory
WORKDIR /var/www

# Install system dependencies, PostgreSQL development tools, and PHP extensions
RUN apk add --no-cache \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    postgresql-dev

RUN docker-php-ext-install pdo pdo_pgsql bcmath

# Get Composer from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
