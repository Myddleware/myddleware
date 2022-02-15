#!/bin/bash
set -e

: ${WWW_DATA_UID:=`stat -c %u /var/www`}
: ${WWW_DATA_GUID:=`stat -c %g /var/www`}

# Change www-data's uid & guid to be the same as directory in host
# Fix cache problems

if [ "$WWW_DATA_UID" != "0" ]; then
    if [ "`id -u www-data`" != "$WWW_DATA_UID" ]; then
        usermod -u $WWW_DATA_UID www-data || true
    fi

    if [ "`id -g www-data`" != "$WWW_DATA_GUID" ]; then
        groupmod -g $WWW_DATA_GUID www-data || true
    fi
fi

# Execute all commands with user www-data
if [ "$1" = "composer" ]; then
    su www-data -s /bin/bash -c "`which php` -d memory_limit=-1 `which composer` ${*:2}"
elif [ -n "$1" ]; then
    su www-data -s /bin/bash -c "$*"
else
    if pgrep "php-fpm" > /dev/null
    then
        echo "Argument required"
    else
        php-fpm
    fi
fi
