#!/usr/bin/env bash

mkdir -p var/cache var/log
chmod -R 700 var/cache
chown -R www-data:www-data var/cache
chmod -R 700 var/log
chown -R www-data:www-data var/log

## Extend Hosts
echo "====[ UPDATE HOSTS ]===="
cat hosts >> /etc/hosts 2>/dev/null || echo "No hosts file to append"
cat /etc/hosts
echo "--"

## Start Apache
echo "====[ START APACHE ]===="
apache2-foreground "$@"
