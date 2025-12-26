FROM php:8.3-apache-bookworm

## Configure PHP
RUN apt-get update && apt-get upgrade -y && \
    apt-get -y install -qq --force-yes mariadb-client libzip-dev libicu-dev zlib1g-dev libc-client-dev libkrb5-dev gnupg2 libaio1 nano && \
    docker-php-ext-configure intl && docker-php-ext-configure imap --with-kerberos --with-imap-ssl && \
    docker-php-ext-install imap exif mysqli pdo pdo_mysql zip intl && \
    echo "short_open_tag=off" >> /usr/local/etc/php/conf.d/syntax.ini && \
    echo "memory_limit=-1" >> /usr/local/etc/php/conf.d/memory_limit.ini && \
    echo "display_errors=0" >> /usr/local/etc/php/conf.d/errors.ini && \
    sed -e 's!DocumentRoot /var/www/html!DocumentRoot /var/www/html/public!' -ri /etc/apache2/sites-available/000-default.conf && \
    apt-get clean && rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer
#RUN pecl install -f ssh2-1.1.2 && docker-php-ext-enable ssh2

COPY composer.json ./composer.json
COPY composer.lock ./composer.lock
RUN composer install

## Install NodeJS
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && \
    apt-get update && apt-get install -y nodejs=20.17.* build-essential && \
    npm install -g npm yarn && \
    apt-get clean && rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*

# Verify Node.js version
RUN node --version

COPY --chown=www-data:www-data . .

# Build packages with yarn
RUN yarn install
RUN yarn run build

## Create Myddleware cache and job directories with proper permissions
RUN mkdir -p /var/www/html/var/cache/dev/myddleware/job \
    /var/www/html/var/cache/prod/myddleware/job && \
    chown -R www-data:www-data /var/www/html/var/cache && \
    chmod -R 775 /var/www/html/var/cache

## Setup Cronjob
RUN apt-get update && apt-get install -y --no-install-recommends cron && \
    apt-get clean && rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*

# Note: rsyslog is not installed because it requires systemd which is not available in Docker
# Cron jobs output directly to log files instead

# Copy cron jobs to /etc/cron.d (system crontab directory)
COPY docker/etc/cron.d/myddleware /etc/cron.d/myddleware
RUN chmod 644 /etc/cron.d/myddleware && \
    mkdir -p /var/log && \
    chmod 777 /var/log && \
    echo "" && \
    echo "=== Cron configuration ===" && \
    echo "Cron file location: /etc/cron.d/myddleware" && \
    echo "Cron file contents:" && \
    cat /etc/cron.d/myddleware && \
    echo "Cron file permissions:" && \
    ls -la /etc/cron.d/myddleware && \
    echo "Log directory permissions:" && \
    ls -la /var/log && \
    echo "=== End cron configuration ==="

## Entrypoint and scripts
COPY ./docker/script/myddleware-foreground.sh /usr/local/bin/myddleware-foreground.sh
COPY ./docker/script/myddleware-cron.sh /usr/local/bin/myddleware-cron.sh
COPY ./docker/script/myddleware-health-check.sh /usr/local/bin/myddleware-health-check.sh
COPY ./docker/script/test-cron.sh /usr/local/bin/test-cron.sh
COPY ./docker/script/verify-cron-execution.sh /usr/local/bin/verify-cron-execution.sh

RUN chmod +x /usr/local/bin/myddleware-*.sh /usr/local/bin/test-cron.sh /usr/local/bin/verify-cron-execution.sh
CMD ["myddleware-foreground.sh"]
