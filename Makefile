#!make

init:
	@docker-compose run --rm --no-deps myddleware bash docker/script/init.sh

clean:
	@docker-compose run --rm --no-deps myddleware bash docker/script/clean.sh

wait:
	@docker-compose up -d mysql
	@docker-compose exec mysql sh -c "while ! (mysqladmin ping -uroot > /dev/null 2>&1); do sleep 1; done"

clean-cache:
	@docker-compose -f docker-compose.yml run --rm myddleware rm -fr var/cache/*

update: init up
	@docker-compose -f docker-compose.yml run --rm myddleware rm -fr var/cache/* vendor
	@docker-compose -f docker-compose.yml run --rm myddleware chmod 777 -R var/cache/
	@docker-compose -f docker-compose.yml run --rm myddleware php composer2.phar install --ignore-platform-reqs --no-scripts
	@echo "Update done."

refresh: init up
	@docker-compose -f docker-compose.yml run --rm myddleware rm -fr var/cache/*
	@docker-compose -f docker-compose.yml run --rm myddleware chmod 777 -R var/cache/
	@docker-compose -f docker-compose.yml up -d --force-recreate myddleware

dump-autoload: init up
	@docker-compose -f docker-compose.yml run --rm myddleware php composer.phar dump-autoload --no-scripts

require-vtiger-client:
	@docker-compose -f docker-compose.yml run --rm myddleware php composer2.phar require javanile/vtiger-client:0.0.28 -vvvv --ignore-platform-reqs --no-scripts

require-woocommerce-client:
	@docker-compose -f docker-compose.yml run --rm myddleware php composer.phar require automattic/woocommerce:^3.0.0 -vvvv --ignore-platform-reqs --no-scripts

setup: js-build setup-database
	@echo "Myddleware files and database setup completed."

setup-database: wait init
	@docker-compose run --rm myddleware bash docker/script/setup-database.sh

schedule:
	@docker-compose -f docker-compose.yml exec myddleware php -f /var/www/html/bin/console myddleware:resetScheduler --env=background
	@docker-compose -f docker-compose.yml exec -e MYDDLEWARE_CRON_RUN=1 -u www-data myddleware php /var/www/html/bin/console myddleware:jobScheduler --env=background

monitoring:
	@docker-compose -f docker-compose.yml exec myddleware bash /var/www/html/dev/script/monitoring.sh

logs: debug
	@docker-compose logs -f myddleware

logs-rotate:
	#@cd var/logs; if [ -f background.log ]; then cp background.log rotate/background.$(shell date +%s).log; truncate -s 0 background.log; fi
	#@cd var/logs; if [ -f dev.log ]; then cp dev.log rotate/dev.$(shell date +%s).log; truncate -s 0 dev.log; fi
	#@cd var/logs; if [ -f monitoring.log ]; then cp monitoring.log rotate/monitoring.$(shell date +%s).log; truncate -s 0 monitoring.log; fi
	#@cd var/logs; if [ -f php.log ]; then cp php.log rotate/php.$(shell date +%s).log; truncate -s 0 php.log; fi
	#@cd var/logs; if [ -f prod.log ]; then cp prod.log rotate/prod.$(shell date +%s).log; truncate -s 0 prod.log; fi
	#@cd var/logs; if [ -f scheduler.log ]; then cp scheduler.log rotate/scheduler.$(shell date +%s).log; truncate -s 0 scheduler.log; fi
	#@cd var/logs; if [ -f vtigercrm.log ]; then cp vtigercrm.log rotate/vtigercrm.$(shell date +%s).log; truncate -s 0 vtigercrm.log; fi
	#@cd var/logs; if [ -f last_query.log ]; then cp last_query.log rotate/last_query.$(shell date +%s).log; truncate -s 0 last_query.log; fi
	#@cd var/logs; if [ -f vtigercrm.json ]; then cp vtigercrm.json rotate/vtigercrm.$(shell date +%s).json; truncate -s 0 vtigercrm.json; fi
	@chmod 777 -R var/logs || true

debug: init
	@docker-compose -f docker-compose.yml -f docker-compose.debug.yml up -d --remove-orphans

prod: init fix
	@docker-compose up -d --remove-orphans

start: prod
	@echo ">>> Myddleware is ready."

recreate: init
	@docker-compose -f docker-compose.yml up -d --remove-orphans --force-recreate

restart: recreate
	@echo ">>> Myddleware is ready."

fix:
	@docker-compose run --rm myddleware bash docker/script/fix.sh || true

bash:
	@docker-compose -f docker-compose.yml exec myddleware bash

update-secret:
	@bash dev/script/update-secret.sh

generate-template:
	@docker-compose -f docker-compose.yml exec myddleware bash dev/script/generate-template.sh

docker-stop-all:
	@docker stop $$(docker ps -qa | grep -v $${MAKEBAT_CONTAINER_ID:-null}) >/dev/null 2>/dev/null || true

reset: clean
	@bash docker/script/reset.sh

install: php-install js-install
	@echo "Myddleware installation complete."

## ---
## PHP
## ---
php-install: up
	@docker-compose run --rm --no-deps myddleware composer install

## ----------
## JavaScript
## ----------
js-install: up
	@docker-compose -f docker-compose.yml -f docker/env/dev.yml run --rm --no-deps myddleware yarn install

js-build: up
	@docker-compose -f docker-compose.yml -f docker/env/dev.yml run --rm --no-deps myddleware yarn run build

## ------
## Docker
## ------
ps:
	@docker-compose ps

up:
	@docker-compose -f docker-compose.yml up -d

down:
	@docker-compose down --remove-orphans

build:
	@docker-compose -f docker-compose.yml -f docker/dev.yml build myddleware

push:
	@docker login
	@docker build -t opencrmitalia/myddleware:v3 .
	@docker push opencrmitalia/myddleware:v3

## -------
## Develop
## -------
dev: \
	init \
	fix \
	dev-clean \
	dev-up \
	dev-prepare-vtiger \
	dev-prepare-mssql

dev-up: build
	@docker-compose -f docker-compose.yml -f docker/dev.yml up --force-recreate -d

dev-clean:
	@docker-compose run --rm myddleware bash -c "cd var/logs; rm -f vtigercrm.log; touch vtigercrm.log; chmod 777 vtigercrm.log"

dev-install: dev-up
	@docker-compose -f docker-compose.yml run --rm myddleware rm -fr vendor
	@docker-compose -f docker-compose.yml run --rm myddleware composer install
	@docker-compose -f docker-compose.yml run --rm myddleware chmod 777 -R vendor

dev-js-install: dev-up
	@docker-compose -f docker-compose.yml -f docker/dev.yml run --rm myddleware yarn install
	@docker-compose -f docker-compose.yml run --rm myddleware chmod 777 -R node_modules yarn.lock

dev-prepare-vtiger:
	@docker-compose exec vtiger1 bash dev/script/vtiger-install.sh
	@docker-compose exec vtiger2 bash dev/script/vtiger-install.sh

dev-prepare-mssql:
	@docker-compose exec mssql sqlcmd -S '127.0.0.1' -U 'sa' -P 'Secret.1234!' -Q 'DROP DATABASE IF EXISTS MSSQL;'
	@docker-compose exec mssql sqlcmd -S '127.0.0.1' -U 'sa' -P 'Secret.1234!' -Q 'CREATE DATABASE MSSQL COLLATE Latin1_General_CS_AS;'
	@docker-compose exec mssql sqlcmd -S '127.0.0.1' -U 'sa' -P 'Secret.1234!' -i /fixtures/mssql.sql

dev-create-random-contacts:
	@docker-compose exec vtiger1 php -f dev/script/create-random-contacts.php

## -------
## Testing
## -------
test-dev: reset install setup dev
	@docker-compose ps

test-debug: reset install setup debug
	@docker-compose ps

test-prod: reset install setup prod
	@docker-compose ps

test-backup: up
	@docker-compose -f docker-compose.yml logs -f backup

test-monitoring:
	@docker-compose -f docker-compose.yml exec myddleware rm -f /var/www/html/var/logs/monitoring.log
	@docker-compose -f docker-compose.yml exec myddleware bash /var/www/html/dev/script/monitoring.sh
	@docker-compose -f docker-compose.yml exec myddleware cat /var/www/html/var/logs/monitoring.log
