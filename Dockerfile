# ─── Base Stage ────────────────────────────────────────────────────────
FROM php:8.2-apache as base

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libcurl4-openssl-dev \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql

# Enable Apache modules
RUN a2enmod rewrite headers

# Set Apache configure environments
ENV APACHE_DOCUMENT_ROOT=/var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

WORKDIR /var/www/html
EXPOSE 80

# ─── Development Stage ──────────────────────────────────────────────────
FROM base as dev
# Install useful debugging suites or xdebug if necessary for developers
# (Left open here)
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

# ─── Production Stage ───────────────────────────────────────────────────
FROM base as prod
# Copy application securely, bypassing volume mounting
COPY . /var/www/html/

# Secure permissions strictly for production
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && chmod -R 777 /var/www/html/logs || true

# Production configurations (e.g., stricter memory limits, opcache)
# RUN docker-php-ext-install opcache

