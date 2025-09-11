#!/bin/bash

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

## Start job
php bin/console myddleware:cronrun --env=background
