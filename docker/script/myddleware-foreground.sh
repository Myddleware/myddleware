#!/usr/bin/env bash

## Install dependencies if needed
echo "====[ INSTALL DEPENDENCIES ]===="
if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
    echo "Installing PHP dependencies..."
    composer install --no-interaction --optimize-autoloader
fi

if [ ! -d "node_modules" ] || [ ! -f "node_modules/.yarn-integrity" ]; then
    echo "Installing Node.js dependencies..."
    yarn install --frozen-lockfile
fi

if [ ! -f "public/build/manifest.json" ]; then
    echo "Building assets..."
    yarn run build
fi
echo "--"

## Setup cache directories and permissions
echo "====[ SETUP CACHE DIRECTORIES ]===="
if [ "$(id -u)" = "0" ]; then
    # Running as root - can create directories and set ownership
    mkdir -p var/cache var/log var/sessions var/cache/dev var/cache/prod
    chown -R www-data:www-data var/cache var/log var/sessions
    chmod -R 775 var/cache var/log var/sessions
    echo "Cache directories created with proper ownership and permissions (as root)"
else
    # Running as www-data - create directories without chown
    mkdir -p var/cache var/log var/sessions var/cache/dev var/cache/prod 2>/dev/null || true
    chmod -R u+w var/cache var/log var/sessions 2>/dev/null || true
    echo "Cache directories setup attempted (as www-data user)"
fi
echo "--"

## Extend Hosts (only if running as root)
echo "====[ UPDATE HOSTS ]===="
if [ "$(id -u)" = "0" ]; then
    cat hosts >> /etc/hosts 2>/dev/null || echo "No hosts file to append"
else
    echo "Skipping hosts update (not running as root)"
fi
cat /etc/hosts 2>/dev/null || echo "No hosts file found"
echo "--"

## Start Cronjob (only if running as root)
echo "====[ PREPARE CRON ]===="
if [ "$(id -u)" = "0" ]; then
    printenv | sed "s/^\(.*\)$/export \\1/g" | grep -E "^export MYSQL_" > /run/crond.env 2>/dev/null || echo "No MySQL env vars found"
    cat crontab.client >> /etc/crontab 2>/dev/null || echo "No crontab.client file found"
    cat /etc/crontab 2>/dev/null || echo "No crontab found"
    rsyslogd 2>/dev/null || echo "rsyslogd not available"
    cron 2>/dev/null || echo "cron not available"
else
    echo "Skipping cron setup (not running as root)"
fi
echo "--"

## Start Apache
echo "====[ START APACHE ]===="
if [ "$(id -u)" = "0" ]; then
    apache2-foreground "$@"
else
    # If not root, switch to root for apache
    exec sudo apache2-foreground "$@"
fi
