# =================================================================
# OmniStock Production Dockerfile (PHP 8.2 + Apache)
# Optimized for Render, Railway, Fly.io, VPS & Docker
# =================================================================

FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libsqlite3-dev \
    zip \
    unzip \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Install required PHP extensions
RUN docker-php-ext-install -j$(nproc) \
    mysqli \
    pdo \
    pdo_mysql \
    pdo_sqlite \
    bcmath \
    opcache \
    zip

# Enable Apache Modules & Overrides for .htaccess
RUN a2enmod rewrite headers deflate expires \
    && sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Configure Production PHP settings
RUN { \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=4000'; \
    echo 'opcache.revalidate_freq=2'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'opcache.enable_cli=1'; \
} > /usr/local/etc/php/conf.d/opcache-recommended.ini

RUN { \
    echo 'memory_limit = 256M'; \
    echo 'upload_max_filesize = 32M'; \
    echo 'post_max_size = 32M'; \
    echo 'date.timezone = UTC'; \
    echo 'session.cookie_httponly = 1'; \
    echo 'session.use_only_cookies = 1'; \
    echo 'session.cookie_samesite = Lax'; \
} > /usr/local/etc/php/conf.d/custom-production.ini

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Copy entrypoint script and set permissions
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/config /var/www/html/database

# Expose default port (Render will override via $PORT)
EXPOSE 80 10000

# Health check
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://localhost:${PORT:-80}/api/health.php || exit 1

# Start container via entrypoint
ENTRYPOINT ["docker-entrypoint.sh"]
