#!/usr/bin/env bash

## Extend Hosts
echo "====[ UPDATE HOSTS ]===="
cat hosts >> /etc/hosts
cat /etc/hosts
echo "--"

## Start Cronjob
printenv | sed "s/^\(.*\)$/export \\1/g" | grep -E "^export MYSQL_" > /run/crond.env
rsyslogd
cron

## Start Apache
apache2-foreground "$@"
