FROM php:8.2-cli

# Install dependencies
RUN apt-get update && apt-get install -y \
    git zip unzip curl libzip-dev libpng-dev libonig-dev \
    libxml2-dev libcurl4-openssl-dev \
    && docker-php-ext-install zip pdo_mysql

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV SIGINT=2
ENV SIGTERM=15
ENV SIGHUP=1
# Install Octane dependencies
RUN composer global require laravel/octane spatie/ignition

# Set working directory
WORKDIR /var/www

# Expose port
EXPOSE 8000
