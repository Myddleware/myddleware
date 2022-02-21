FROM --platform=linux/amd64 php:7.4.26-apache

## Configure PHP
RUN apt-get update && apt-get upgrade -y && \
    apt-get -y install -qq --force-yes mariadb-client libzip-dev libicu-dev zlib1g-dev libc-client-dev libkrb5-dev gnupg2 libaio1 rsync && \
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

## Install PHP Accelerators
RUN pecl install apcu \
    && pecl install apcu_bc-1.0.3 \
    && docker-php-ext-enable apcu --ini-name 10-docker-php-ext-apcu.ini \
    && docker-php-ext-enable apc --ini-name 20-docker-php-ext-apc.ini

## Intall NodeJS
RUN curl -fsSL https://deb.nodesource.com/setup_current.x | bash - && \
    apt-get update && apt-get install -y nodejs build-essential && npm install -g npm yarn && \
    apt-get clean && rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*

COPY --chown=www-data:www-data . .

# Build packages with yarn
RUN yarn install
RUN yarn run build

RUN rsync -Rr ./public/build/ ./public/build/
## Setup Cronjob
# RUN echo "cron.* /var/log/cron.log" >> /etc/rsyslog.conf && rm -fr /etc/cron.* && mkdir /etc/cron.d
# COPY docker/etc/crontab /etc/
# RUN chmod 600 /etc/crontab

## Entrypoint and scripts
COPY ./docker/script/myddleware-foreground.sh /usr/local/bin/myddleware-foreground.sh


RUN chown www-data:www-data ./var ./var/cache ./var/cache/*
RUN chmod 755 ./var/cache ./var/cache/*
RUN chmod +x /usr/local/bin/myddleware-*.sh
CMD ["myddleware-foreground.sh"]
