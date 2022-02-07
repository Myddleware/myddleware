FROM php:7.4.26-apache
LABEL maintainer="Francesco Bianco <francescobianco@opencrmitalia.com>"

## Configure PHP
RUN apt-get update && apt-get upgrade -y && \
    apt-get -y install -qq --force-yes libzip-dev git zlib1g-dev libc-client-dev libkrb5-dev cron rsyslog unzip libssh2-1-dev gnupg2 alien libaio1 nano vim net-tools iputils-ping telnet && \
    docker-php-ext-configure imap --with-kerberos --with-imap-ssl && \
    docker-php-ext-install imap exif mysqli pdo pdo_mysql zip && \
    echo "memory_limit=-1" >> /usr/local/etc/php/conf.d/memory_limit.ini && \
    echo "display_errors=0" >> /usr/local/etc/php/conf.d/errors.ini && \
    sed -e 's!DocumentRoot /var/www/html!DocumentRoot /var/www/html/public!' -ri /etc/apache2/sites-available/000-default.conf && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer && \
    apt-get clean && rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*
#RUN pecl install -f ssh2-1.1.2 && docker-php-ext-enable ssh2

## Intall NodeJS
RUN curl -fsSL https://deb.nodesource.com/setup_current.x | bash - && \
    apt-get update && apt-get install -y nodejs build-essential && npm install -g npm && \
    apt-get clean && rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*

