FROM php:fpm

COPY docker/php-fpm/php.ini /usr/local/etc/php/

COPY docker/php-fpm/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN apt-get update && apt-get install -y zlib1g-dev libicu-dev wget git zip vim bash libxml2-dev libpng-dev \
    && docker-php-ext-install pdo pdo_mysql intl

#zip
RUN apt-get install -y \
        libzip-dev \
        zip \
    && docker-php-ext-configure zip \
    && docker-php-ext-install zip

#imap
RUN apt-get update && apt-get install -y libc-client-dev libkrb5-dev && rm -r /var/lib/apt/lists/*
RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install imap

#imagick
RUN apt-get update && apt-get install -y \
    libmagickwand-dev --no-install-recommends \
    && pecl install imagick \
    && docker-php-ext-enable imagick


RUN apt-get update && \
    apt-get install -y \
    libfreetype6-dev \
    libwebp-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    nano \
    libgmp-dev \
    libldap2-dev \
    netcat \
    sqlite3 \
    libsqlite3-dev && \
    docker-php-ext-install gd pdo pdo_mysql pdo_sqlite zip gmp bcmath pcntl ldap sysvmsg exif

# Install composer

# Install Composer globally
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && php composer-setup.php && php -r "unlink('composer-setup.php');" && mv composer.phar /usr/local/bin/composer

# Symfony Panther
RUN apt-get update && apt-get install -y libzip-dev zlib1g-dev chromium chromium-driver && docker-php-ext-install zip
ENV PANTHER_NO_HEADLESS 0
ENV PANTHER_NO_SANDBOX 1
ENV PANTHER_WEB_SERVER_PORT 9800
ENV PANTHER_EXTERNAL_BASE_URL http://localhost:9080
ENV PANTHER_CHROME_ARGUMENTS='--disable-dev-shm-usage'


WORKDIR /var/www

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
