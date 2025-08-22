#!/usr/bin/env bash

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
