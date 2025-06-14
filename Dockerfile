FROM --platform=linux/amd64 php:8.2-apache

# Add labels for better maintainability
LABEL maintainer="Your Name <your.email@example.com>"
LABEL description="Your application description"

# Set working directory
WORKDIR /var/www/html

# Install system dependencies and PHP extensions in a single layer
RUN apt-get update && apt-get upgrade -y && \
    apt-get install -y --no-install-recommends \
        mariadb-client \
        libzip-dev \
        libicu-dev \
        zlib1g-dev \
        libc-client-dev \
        libkrb5-dev \
        gnupg2 \
        libaio1 && \
    docker-php-ext-configure intl && \
    docker-php-ext-configure imap --with-kerberos --with-imap-ssl && \
    docker-php-ext-install \
        imap \
        exif \
        mysqli \
        pdo \
        pdo_mysql \
        zip \
        intl && \
    # PHP configurations
    echo "short_open_tag=off" >> /usr/local/etc/php/conf.d/syntax.ini && \
    echo "memory_limit=-1" >> /usr/local/etc/php/conf.d/memory_limit.ini && \
    echo "display_errors=0" >> /usr/local/etc/php/conf.d/errors.ini && \
    # Update Apache configuration
    sed -e 's!DocumentRoot /var/www/html!DocumentRoot /var/www/html/public!' -ri /etc/apache2/sites-available/000-default.conf && \
    # Clean up
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* && \
    docker-php-ext-install opcache && \
    echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.interned_strings_buffer=16" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "realpath_cache_size=4096K" >> /usr/local/etc/php/conf.d/php.ini && \
    echo "realpath_cache_ttl=600" >> /usr/local/etc/php/conf.d/php.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js (using specific version)
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - && \
    apt-get update && \
    apt-get install -y nodejs build-essential && \
    npm install -g npm yarn && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Copy application files first
COPY --chown=www-data:www-data . .

# Create necessary directories with proper permissions
RUN mkdir -p /var/www/html/var/cache /var/www/html/var/log /var/www/html/var/sessions && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Switch to www-data user for dependency installation
USER www-data

# Install PHP dependencies
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Install Node.js dependencies only (build will happen at startup)
RUN yarn install --frozen-lockfile

# Switch back to root to copy scripts and set permissions
USER root

# Copy scripts and set permissions
COPY ./docker/script/myddleware-foreground.sh /usr/local/bin/
COPY ./docker/script/myddleware-cron.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/myddleware-*.sh

# Add healthcheck
HEALTHCHECK --interval=30s --timeout=3s --start-period=30s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Switch to non-root user
USER www-data

CMD ["myddleware-foreground.sh"]
