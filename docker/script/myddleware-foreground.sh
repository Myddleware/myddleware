#!/usr/bin/env bash

## Check for .env.local at startup
echo "====[ STARTUP FILE CHECK ]===="
echo "Checking for .env.local file at startup..."
ls -la .env.local 2>/dev/null || echo ".env.local not found at startup!"
echo "Checking for test file..."
ls -la test-file.txt 2>/dev/null || echo "test-file.txt not found at startup!"
echo "Current directory: $(pwd)"
echo "Current user: $(whoami)"

## Extend Hosts
echo "====[ UPDATE HOSTS ]===="
cat hosts >> /etc/hosts
cat /etc/hosts
echo "--"

## Start Cronjob
echo "====[ PREPARE CRON ]===="
printenv | sed "s/^\(.*\)$/export \\1/g" | grep -E "^export MYSQL_" > /run/crond.env
cat crontab.client >> /etc/crontab
cat /etc/crontab
echo "--"
rsyslogd
cron

## Generate JS routing (requires runtime environment)
echo "====[ GENERATE JS ROUTING ]===="
php bin/console fos:js-routing:dump --format=json --target=public/js/fos_js_routes.json

## Start Apache
echo "====[ START APACHE ]===="
apache2-foreground "$@"
