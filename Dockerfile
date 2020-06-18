FROM php:7.2.27-apache

RUN apt-get update && apt-get upgrade -y && \
    apt-get -y install -qq --force-yes git zlib1g-dev libc-client-dev libkrb5-dev cron rsyslog unzip libssh2-1-dev gnupg2 alien libaio1 && \
    docker-php-ext-configure imap --with-kerberos --with-imap-ssl && \
    docker-php-ext-install imap exif mysqli pdo pdo_mysql zip && \
    echo "memory_limit=-1" >> /usr/local/etc/php/conf.d/memory_limit.ini && \
    pecl install -f ssh2-1.1.2 && \
    docker-php-ext-enable ssh2 && \
    sed -e 's!DocumentRoot /var/www/html!DocumentRoot /var/www/html/web!' \
        -ri /etc/apache2/sites-available/000-default.conf

RUN pecl install -f xdebug && \
    docker-php-ext-enable xdebug && \
    echo "xdebug.remote_enable = 1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    # host.docker.internal does not work on Linux yet: https://github.com/docker/for-linux/issues/264
    # Workaround:
    # apt install iproute2 && ip -4 route list match 0/0 | awk '{print $3 " host.docker.internal"}' >> /etc/hosts \
    echo "xdebug.remote_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.remote_autostart = 0" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.remote_connect_back = 0" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.remote_port = 9000" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.remote_handler = 'dbgp'" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.remote_mode = req" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

RUN curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - && \
    curl https://packages.microsoft.com/config/debian/10/prod.list > /etc/apt/sources.list.d/mssql-release.list && \
    apt-get update && \
    ACCEPT_EULA=Y apt-get install --no-install-recommends -y msodbcsql17 unixodbc-dev && \
    pecl install -f sqlsrv pdo_sqlsrv && \
    docker-php-ext-enable sqlsrv pdo_sqlsrv && \
    sed -i 's,^\(MinProtocol[ ]*=\).*,\1'TLSv1.0',g' /etc/ssl/openssl.cnf && \
    sed -i 's,^\(CipherString[ ]*=\).*,\1'DEFAULT@SECLEVEL=1',g' /etc/ssl/openssl.cnf

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

RUN curl -sS https://platform.sh/cli/installer | php && \
    ln -s /root/.platformsh/bin/platform /usr/local/bin/platform

RUN echo "cron.* /var/log/cron.log" >> /etc/rsyslog.conf && rm -fr /etc/cron.* && mkdir /etc/cron.d
COPY crontab /etc/
RUN chmod 600 /etc/crontab

RUN echo "display_errors=0" >> /usr/local/etc/php/conf.d/errors.ini

RUN cp /usr/local/bin/apache2-foreground /usr/local/bin/apache2-foreground-inherit; \
    { \
        echo '#!/bin/bash'; \
        echo 'printenv | sed "s/^\(.*\)$/export \\1/g" | grep -E "^export MYSQL_" > /run/crond.env'; \
        echo 'rsyslogd'; \
        echo 'cron'; \
        echo 'apache2-foreground-inherit "$@"'; \
    } > /usr/local/bin/apache2-foreground
