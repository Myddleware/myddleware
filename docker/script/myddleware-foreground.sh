#!/usr/bin/env bash

mkdir -p var/cache var/log
chmod -R 700 var/cache
chown -R www-data:www-data var/cache
chmod -R 700 var/log
chown -R www-data:www-data var/log

## Extend Hosts
echo "====[ UPDATE HOSTS ]===="
cat hosts >> /etc/hosts
cat /etc/hosts
echo "--"

## Start logging services
echo "====[ START LOGGING SERVICES ]===="
service rsyslog start

## Start cron daemon
echo "====[ START CRON DAEMON ]===="
service cron start

## Verify cron configuration
echo "====[ VERIFY CRON CONFIGURATION ]===="
echo "Checking /etc/cron.d/ directory:"
ls -la /etc/cron.d/
echo ""
echo "Cron daemon started. Sleeping 2 seconds to ensure initialization..."
sleep 2
echo ""

## Start Apache
echo "====[ START APACHE ]===="
apache2-foreground "$@"
