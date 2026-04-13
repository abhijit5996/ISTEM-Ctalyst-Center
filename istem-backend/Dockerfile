# Use PHP with Apache
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    && docker-php-ext-install pdo pdo_mysql mysqli mbstring zip

# Enable Apache rewrite module
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Laravel dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Set Apache Document Root to Laravel public folder (ONLY ONCE)
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Set permissions (VERY IMPORTANT for Laravel)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Expose default port
EXPOSE 80

# Start Apache with dynamic PORT (Render-compatible)
CMD ["sh", "-c", "PORT=${PORT:-80} && sed -i \"s/Listen 80/Listen ${PORT}/\" /etc/apache2/ports.conf && sed -i \"s|<VirtualHost \\*:80>|<VirtualHost *:${PORT}>|\" /etc/apache2/sites-available/000-default.conf && php artisan config:clear && php artisan cache:clear && php artisan config:cache && php artisan migrate --force && apache2-foreground"]