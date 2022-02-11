#!/usr/bin/env bash

#@[ -f hosts ] || touch hosts
#@[ -f .env ] || cp .env.example .env
#@[ -f scheduler.sh ] || touch scheduler.sh
#@[ -f crontab.client ] || touch crontab.client
#@cd app/config/public/ && [ -f parameters_smtp.yml ] || cp parameters_smtp.yml.default parameters_smtp.yml
#@cd src/Myddleware/RegleBundle/Custom/Solutions && [ -f database.client.php ] || cp  ../../../../../var/solutions/database.client.php database.client.php
#@cd src/Myddleware/RegleBundle/Custom/Solutions && [ -f file.client.php ] || cp  ../../../../../var/solutions/file.client.php file.client.php
#@cd src/Myddleware/RegleBundle/Custom/Solutions && [ -f mautic.client.php ] || cp  ../../../../../var/solutions/mautic.client.php mautic.client.php
#@cd src/Myddleware/RegleBundle/Custom/Solutions && [ -f microsoftsql.client.php ] || cp  ../../../../../var/solutions/microsoftsql.client.php microsoftsql.client.php
#@cd src/Myddleware/RegleBundle/Custom/Solutions && [ -f mysql.client.php ] || cp  ../../../../../var/solutions/mysql.client.php mysql.client.php
#@cd src/Myddleware/RegleBundle/Custom/Solutions && [ -f oracledb.client.php ] || cp  ../../../../../var/solutions/oracledb.client.php oracledb.client.php
#@cd src/Myddleware/RegleBundle/Custom/Solutions && [ -f vtigercrm.client.php ] || cp  ../../../../../var/solutions/vtigercrm.client.php vtigercrm.client.php
#@cd src/Myddleware/RegleBundle/Custom/Solutions && [ -f woocommerce.client.php ] || cp  ../../../../../var/solutions/woocommerce.client.php woocommerce.client.php
#@cd src/Myddleware/RegleBundle/Custom && [ -f Custom.json ] || cp  ../../../../var/custom/Custom.json Custom.json
#@cd var/databases && [ -d filebrowser.db ] && rm -fr filebrowser.db || true; touch filebrowser.db

## Initialize environment
echo "Initialize environment"
if [ ! -f .env.local ]; then
  cp docker/env/local.init .env.local
  php -r 'echo "APP_SECRET=".md5(microtime());' >> .env.local
fi
if [ ! -f .env.docker ]; then
  cp docker/env/docker.init .env.docker
fi
chmod 777 .env.local .env.docker

## Fix files permissions
[ -d var/log ] && chmod 777 -R var/log || true
[ -d var/logs ] && chmod 777 -R var/logs || true
