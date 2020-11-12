#!/usr/bin/env bash



## Start Cronjob
printenv | sed "s/^\(.*\)$/export \\1/g" | grep -E "^export MYSQL_" > /run/crond.env
rsyslogd
cron

## Start Apache
apache2-foreground "$@"
