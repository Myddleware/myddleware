FROM php:7.4-fpm

COPY docker/php-fpm/php.ini /usr/local/etc/php/

COPY docker/php-fpm/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN apt-get update && apt-get install -y \ 
    zlib1g-dev unzip libicu-dev wget git \
    && docker-php-ext-configure intl \ 
    && docker-php-ext-install pdo pdo_mysql intl \
    && docker-php-ext-enable intl

#zip
RUN apt-get update && apt-get install -y \
    zip \
    && docker-php-ext-configure zip \
    && docker-php-ext-install zip

# Get nodejs
RUN curl -sL https://deb.nodesource.com/setup_14.x  | bash -

#imap
RUN apt-get update && apt-get install -y libc-client-dev libkrb5-dev && rm -r /var/lib/apt/lists/*
RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install imap

# Not really sure we need this ???
RUN git clone https://github.com/nikic/php-ast.git && \
    cd php-ast && \
    phpize && \
    ./configure --enable-ast && \
    make install && \
    docker-php-ext-enable ast

RUN apt-get update && \
    apt-get install -y \
    nodejs \
    netcat \
    sqlite3 \
    docker-php-ext-install pdo pdo_mysql pdo_sqlite


# Install composer
ADD docker/php-fpm/install-composer.sh /install-composer.sh
RUN chmod +x /install-composer.sh && /install-composer.sh && rm -f /install-composer.sh

WORKDIR /var/www

# Install Symfony CLI globally
RUN wget https://get.symfony.com/cli/installer -O - | bash && \
    mv /root/.symfony/bin/symfony /usr/local/bin/symfony

# Install yarn
RUN curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add - && \
    echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list && \
    apt-get update && apt-get install -y yarn

# install node_modules (javascript)
# RUN yarn install

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
