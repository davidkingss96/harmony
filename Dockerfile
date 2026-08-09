FROM php:8.2-apache

# System tools (unzip required by Composer)
RUN apt-get update && apt-get install -y --no-install-recommends unzip \
    && rm -rf /var/lib/apt/lists/*

# Install MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Install Composer (for dependency management)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Enable Apache modules
RUN a2enmod rewrite && a2enmod headers

# Set working directory
WORKDIR /var/www/html

# Copy Apache configuration
COPY apache.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 80
