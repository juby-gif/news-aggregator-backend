# Base image
FROM php:8.0-apache

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer.* ./

# Install dependencies
RUN apt-get update \
    && apt-get install -y \
    zip \
    unzip \
    git \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install --no-interaction --optimize-autoloader --no-dev \
    && chown -R www-data:www-data /var/www/html \
    && a2enmod rewrite

# Copy project files
COPY . .

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache

# Generate key
RUN php artisan key:generate

# Expose port 80
EXPOSE 80

# Set the command to run the app
CMD ["apache2-foreground"]
