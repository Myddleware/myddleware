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

# Composer install
composer install
# Composer update (optional?)
# Yarn install assets (node_modules)
yarn install

# Create / update database  
# Comment faire pour lancer l'une ou l'autre de ces 2 commandes (genre si la 1 fail alors fais la 2)
php bin/console doctrine:database:create --if-not-exist
php bin/console doctrine:schema:update --force
# Load fixtures 
# php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
# php bin/console doctrine:fixtures:load --no-interaction --append

# Generate doctrine migrations

# Validate Doctrine ORM mapping

# Run Webpack Encore to compile assets (dev)
# Run Webpack Encore to compile assets (prod)

