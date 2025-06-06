#!/usr/bin/env bash

## Install dependencies if needed
echo "====[ INSTALL DEPENDENCIES ]===="
if [ ! -d "vendor" ] || [ ! -d "node_modules" ]; then
    echo "Installing PHP dependencies..."
    composer install --no-interaction --optimize-autoloader
    echo "Installing Node.js dependencies..."
    yarn install --frozen-lockfile
    echo "Building assets..."
    yarn run build
fi
echo "--"

## Extend Hosts
echo "====[ UPDATE HOSTS ]===="
cat hosts >> /etc/hosts 2>/dev/null || echo "No hosts file to append"
cat /etc/hosts
echo "--"

## Start Cronjob
echo "====[ PREPARE CRON ]===="
printenv | sed "s/^\(.*\)$/export \\1/g" | grep -E "^export MYSQL_" > /run/crond.env 2>/dev/null || echo "No MySQL env vars found"
cat crontab.client >> /etc/crontab 2>/dev/null || echo "No crontab.client file found"
cat /etc/crontab 2>/dev/null || echo "No crontab found"
echo "--"
rsyslogd 2>/dev/null || echo "rsyslogd not available"
cron 2>/dev/null || echo "cron not available"

## Start Apache
echo "====[ START APACHE ]===="
apache2-foreground "$@"
