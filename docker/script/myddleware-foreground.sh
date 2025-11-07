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

## Start cron daemon
echo "====[ START CRON DAEMON ]===="
service cron start
service rsyslog start

## Start Apache
echo "====[ START APACHE ]===="
apache2-foreground "$@"
