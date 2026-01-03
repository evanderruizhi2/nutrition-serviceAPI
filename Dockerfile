# Gunakan PHP 8.2 + Apache
FROM php:8.2-apache

# Install dependencies sistem + ekstensi PHP
RUN apt-get update && apt-get install -y \
    libicu-dev \
    zip unzip git curl \
    && docker-php-ext-install intl pdo pdo_mysql \
    && apt-get clean

# Install Composer (latest) secara global
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory di container
WORKDIR /var/www/html

# Set Apache DocumentRoot ke public/
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf

# Copy seluruh project ke container
COPY . /var/www/html

# Install dependencies PHP lewat Composer
RUN composer install --no-dev --optimize-autoloader

# Set permission folder
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port Apache
EXPOSE 80

# Jalankan Apache di foreground
CMD ["apache2-foreground"]
