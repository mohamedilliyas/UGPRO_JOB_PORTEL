# Production PHP 8.2 Apache Image (Compatible with Hugging Face, Render, Docker & Cloud VPS)
FROM php:8.2-apache

# Install required system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd mysqli pdo pdo_mysql zip \
    && a2enmod rewrite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Configure Apache to listen on port 7860 (Hugging Face default) and 80 (Standard)
RUN echo "Listen 7860" >> /etc/apache2/ports.conf \
    && sed -ri -e 's!<VirtualHost \*:80>!<VirtualHost \*:80 \*:7860>!g' /etc/apache2/sites-available/*.conf

# Configure custom php.ini settings
RUN echo "upload_max_filesize = 20M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 25M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini

# Copy project files
WORKDIR /var/www/html
COPY . /var/www/html/

# Create and grant full permissions for upload directories
RUN mkdir -p uploads/profiles uploads/logos uploads/resumes \
    && chmod -R 777 /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/uploads

EXPOSE 80 7860

CMD ["apache2-foreground"]
