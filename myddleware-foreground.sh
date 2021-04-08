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

## Start Apache
echo "====[ START APACHE ]===="
apache2-foreground "$@"
