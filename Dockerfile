FROM --platform=linux/amd64 php:7.4.26-apache
LABEL maintainer="Francesco Bianco <francescobianco@opencrmitalia.com>"

## Configure PHP
RUN apt-get update && apt-get upgrade -y && \
    apt-get -y install -qq --force-yes rsync mariadb-client libzip-dev libicu-dev git zlib1g-dev libc-client-dev libkrb5-dev cron rsyslog unzip libssh2-1-dev gnupg2 alien libaio1 nano vim net-tools iputils-ping telnet && \
    docker-php-ext-configure intl && docker-php-ext-configure imap --with-kerberos --with-imap-ssl && \
    docker-php-ext-install imap exif mysqli pdo pdo_mysql zip intl && \
    echo "short_open_tag=off" >> /usr/local/etc/php/conf.d/syntax.ini && \
    echo "memory_limit=-1" >> /usr/local/etc/php/conf.d/memory_limit.ini && \
    echo "display_errors=0" >> /usr/local/etc/php/conf.d/errors.ini && \
    sed -e 's!DocumentRoot /var/www/html!DocumentRoot /var/www/html/public!' -ri /etc/apache2/sites-available/000-default.conf && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer && \
    apt-get clean && rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*
#RUN pecl install -f ssh2-1.1.2 && docker-php-ext-enable ssh2

## Install PHP Accelerators
RUN pecl install apcu \
    && pecl install apcu_bc-1.0.3 \
    && docker-php-ext-enable apcu --ini-name 10-docker-php-ext-apcu.ini \
    && docker-php-ext-enable apc --ini-name 20-docker-php-ext-apc.ini

## Intall NodeJS
RUN curl -fsSL https://deb.nodesource.com/setup_current.x | bash - && \
    apt-get update && apt-get install -y nodejs build-essential && npm install -g npm yarn && \
    apt-get clean && rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*

## Install Xdebug
RUN pecl install -f xdebug && \
    docker-php-ext-enable xdebug && \
    echo "xdebug.remote_enable = 1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.remote_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.remote_autostart = 0" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.remote_connect_back = 0" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.remote_port = 9000" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.remote_handler = 'dbgp'" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.remote_mode = req" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

## Install MS Database
#RUN curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - && \
#     curl https://packages.microsoft.com/config/debian/10/prod.list > /etc/apt/sources.list.d/mssql-release.list && \
#    apt-get update && \
#    apt-get install --no-install-recommends -y msodbcsql17 unixodbc-dev && \
#    pecl install -f sqlsrv pdo_sqlsrv && \
#    docker-php-ext-enable sqlsrv pdo_sqlsrv && \
#    sed -i 's,^\(MinProtocol[ ]*=\).*,\1'TLSv1.0',g' /etc/ssl/openssl.cnf && \
#    sed -i 's,^\(CipherString[ ]*=\).*,\1'DEFAULT@SECLEVEL=1',g' /etc/ssl/openssl.cnf

## Install Oracle Database
RUN curl "https://download.oracle.com/otn_software/linux/instantclient/195000/oracle-instantclient19.5-sqlplus-19.5.0.0.0-1.x86_64.rpm" -o "/mnt/oracle-instant-sqlplus.rpm" && \
    curl "https://download.oracle.com/otn_software/linux/instantclient/195000/oracle-instantclient19.5-basic-19.5.0.0.0-1.x86_64.rpm" -o "/mnt/oracle-instant-basic.rpm" && \
    curl "https://download.oracle.com/otn_software/linux/instantclient/195000/oracle-instantclient19.5-devel-19.5.0.0.0-1.x86_64.rpm" -o "/mnt/oracle-instant-devel.rpm" && \
    curl "https://download.oracle.com/otn_software/linux/instantclient/195000/oracle-instantclient19.5-odbc-19.5.0.0.0-1.x86_64.rpm" -o "/mnt/oracle-instant-odbc.rpm" && \
    curl "https://download.oracle.com/otn_software/linux/instantclient/195000/oracle-instantclient19.5-tools-19.5.0.0.0-1.x86_64.rpm" -o "/mnt/oracle-instant-tools.rpm" && \
    alien -i /mnt/oracle-instant-sqlplus.rpm && \
    alien -i /mnt/oracle-instant-basic.rpm && \
    alien -i /mnt/oracle-instant-devel.rpm && \
    alien -i /mnt/oracle-instant-odbc.rpm && \
    alien -i /mnt/oracle-instant-tools.rpm && \
    ln -s /usr/lib/oracle/19.5/client64/lib/libsqora.so.19.1 /usr/lib/libsqora.so && \
    rm /mnt/* && \
    export LD_LIBRARY_PATH=/usr/lib/oracle/19.5/client64/lib && \
    export ORACLE_HOME=/usr/lib/oracle/19.5/client64 && \
    export C_INCLUDE_PATH=/usr/include/oracle/19.5/client64 && \
    docker-php-ext-install oci8 pdo_oci

## Install Platform tool
RUN curl -sS https://platform.sh/cli/installer | php && \
    ln -s /root/.platformsh/bin/platform /usr/local/bin/platform

## Setup Cronjob
RUN echo "cron.* /var/log/cron.log" >> /etc/rsyslog.conf && rm -fr /etc/cron.* && mkdir /etc/cron.d
COPY docker/etc/crontab /etc/
RUN chmod 600 /etc/crontab

## Install DBLIB
#RUN apt-get update && \
#    apt-get install -y freetds-bin freetds-dev freetds-common libct4 libsybdb5 tdsodbc libfreetype6-dev libjpeg62-turbo-dev libmcrypt-dev zlib1g-dev libicu-dev g++ libc-client-dev && \
#    docker-php-ext-configure pdo_dblib --with-libdir=/lib/x86_64-linux-gnu && \
#    docker-php-ext-configure intl && \
#    docker-php-ext-install pdo_dblib && \
#    docker-php-ext-install intl && \
#    docker-php-ext-install mbstring && \
#    docker-php-ext-enable intl mbstring pdo_dblib

## Sysadmin tools
RUN apt-get update && apt-get upgrade -y && \
    apt-get -y install -qq --force-yes nano vim net-tools iputils-ping telnet

## Entrypoint and scripts
COPY ./docker/script/myddleware-cron.sh /usr/local/bin/myddleware-cron.sh
COPY ./docker/script/myddleware-foreground.sh /usr/local/bin/myddleware-foreground.sh
COPY ./docker/script/myddleware-monitor.sh /usr/local/bin/myddleware-monitor.sh
RUN chmod +x /usr/local/bin/myddleware-*.sh
CMD ["myddleware-foreground.sh"]
