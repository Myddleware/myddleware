FROM php:7.4-fpm

COPY docker/php-fpm/php.ini /usr/local/etc/php/

COPY docker/php-fpm/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN apt-get update && apt-get install -y zlib1g-dev libicu-dev wget git zip vim libxml2-dev libpng-dev \
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

RUN git clone https://github.com/nikic/php-ast.git && \
    cd php-ast && \
    phpize && \
    ./configure --enable-ast && \
    make install && \
    docker-php-ext-enable ast

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
    docker-php-ext-install pdo pdo_mysql pdo_sqlite zip gmp bcmath pcntl ldap sysvmsg exif

# Install composer
ADD docker/php-fpm/install-composer.sh /install-composer.sh
RUN chmod +x /install-composer.sh && /install-composer.sh && rm -f /install-composer.sh

WORKDIR /var/www

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
