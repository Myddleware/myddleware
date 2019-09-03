FROM php:7.1.32-apache

RUN apt-get update && \
    apt-get install --no-install-recommends -y zlib1g-dev libc-client-dev libkrb5-dev cron rsyslog unzip && \
    docker-php-ext-configure imap --with-kerberos --with-imap-ssl && \
    docker-php-ext-install imap exif mysqli pdo pdo_mysql zip && \
    sed -e 's!DocumentRoot /var/www/html!DocumentRoot /var/www/html/web!' \
        -ri /etc/apache2/sites-available/000-default.conf
