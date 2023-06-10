# Base image
FROM php:8.0-apache

# Set the working directory
WORKDIR /var/www/html

# Install dependencies
RUN apt-get update \
    && apt-get install -y \
    zip \
    unzip \
    git \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy the source code
COPY . .

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache

# Generate application key
RUN php artisan key:generate

# Expose port 8000
EXPOSE 8000

# Set the command to run the app
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
