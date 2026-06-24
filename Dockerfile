FROM php:8.2-cli-alpine

# Install system dependencies and build libraries
RUN apk add --no-cache \
    git \
    unzip \
    zip \
    sqlite-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev

# Install PHP extensions
RUN docker-php-ext-install pdo_sqlite gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Command to run tests
CMD ["vendor/bin/phpunit"]
