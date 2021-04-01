#!make

init:
	@[ -f hosts ] || touch hosts
	@[ -f .env ] || cp .env.example .env
	@[ -f scheduler.sh ] || touch scheduler.sh
	@cd src/Myddleware/RegleBundle/Custom/Solutions && [ -f database.client.php ] || cp  ../../../../../var/solutions/database.client.php database.client.php
	@cd src/Myddleware/RegleBundle/Custom/Solutions && [ -f mautic.client.php ] || cp  ../../../../../var/solutions/mautic.client.php mautic.client.php
	@cd src/Myddleware/RegleBundle/Custom/Solutions && [ -f microsoftsql.client.php ] || cp  ../../../../../var/solutions/microsoftsql.client.php microsoftsql.client.php
	@cd src/Myddleware/RegleBundle/Custom/Solutions && [ -f mysql.client.php ] || cp  ../../../../../var/solutions/mysql.client.php mysql.client.php
	@cd src/Myddleware/RegleBundle/Custom/Solutions && [ -f oracledb.client.php ] || cp  ../../../../../var/solutions/oracledb.client.php oracledb.client.php
	@cd src/Myddleware/RegleBundle/Custom/Solutions && [ -f vtigercrm.client.php ] || cp  ../../../../../var/solutions/vtigercrm.client.php vtigercrm.client.php
	@cd src/Myddleware/RegleBundle/Custom/Solutions && [ -f woocommerce.client.php ] || cp  ../../../../../var/solutions/woocommerce.client.php woocommerce.client.php
	@cd var/databases && [ -d filebrowser.db ] && rm -fr filebrowser.db || true; touch filebrowser.db

clean: init
	@rm -fr .git/.idea >/dev/null 2>/dev/null || true
	@mv .idea .git/.idea >/dev/null 2>/dev/null || true
	@git clean -dfx >/dev/null 2>/dev/null || true
	@mv .git/.idea .idea >/dev/null 2>/dev/null || true

sleep:
	@sleep 5

ps: init
	@docker-compose ps

up: init
	@docker-compose -f docker-compose.yml up -d

down:
	@docker-compose down --remove-orphans

build:
	@docker-compose build myddleware

install: init up
	@docker-compose -f docker-compose.yml run --rm myddleware php composer.phar install --ignore-platform-reqs --no-scripts
	@echo "Install done."

clean-cache:
	@docker-compose -f docker-compose.yml run --rm myddleware rm -fr var/cache/*

update: init up
	@docker-compose -f docker-compose.yml run --rm myddleware rm -fr var/cache/* vendor
	@docker-compose -f docker-compose.yml run --rm myddleware chmod 777 -R var/cache/
	@docker-compose -f docker-compose.yml run --rm myddleware php composer.phar install --ignore-platform-reqs --no-scripts
	@echo "Update done."

refresh: init up
	@docker-compose -f docker-compose.yml run --rm myddleware rm -fr var/cache/*
	@docker-compose -f docker-compose.yml run --rm myddleware chmod 777 -R var/cache/
	@docker-compose -f docker-compose.yml up -d --force-recreate myddleware

dump-autoload: init up
	@docker-compose -f docker-compose.yml run --rm myddleware php composer.phar dump-autoload --no-scripts

require-vtiger-client:
	@docker-compose -f docker-compose.yml run --rm myddleware php composer.phar require javanile/vtiger-client:0.0.25 -vvvv --ignore-platform-reqs --no-scripts

require-woocommerce-client:
	@docker-compose -f docker-compose.yml run --rm myddleware php composer.phar require automattic/woocommerce:^3.0.0 -vvvv --ignore-platform-reqs --no-scripts

setup-files:
	@docker-compose -f docker-compose.yml run --rm myddleware php composer.phar run-script post-install-cmd
	@docker-compose -f docker-compose.yml run --rm myddleware chmod 777 -R var/cache/ var/logs/ || true

setup-database: up sleep
	@docker-compose -f docker-compose.yml exec myddleware bash prepare-database.sh

setup: setup-files setup-database
	@echo "Setup Myddleware files and database: OK!"

logs: debug
	@docker-compose logs -f myddleware

logs-rotate:
	@cd var/logs; if [ -f background.log ]; then cp background.log rotate/background.$(shell date +%s).log; truncate -s 0 background.log; fi
	@cd var/logs; if [ -f prod.log ]; then cp prod.log rotate/prod.$(shell date +%s).log; truncate -s 0 prod.log; fi
	@cd var/logs; if [ -f vtigercrm.log ]; then cp vtigercrm.log rotate/vtigercrm.$(shell date +%s).log; truncate -s 0 vtigercrm.log; fi
	@chmod 777 -R var/logs || true

debug: init
	@docker-compose -f docker-compose.yml up -d --remove-orphans

dev: init
	@docker-compose run --rm myddleware bash -c "cd var/logs; rm -f vtigercrm.log; touch vtigercrm.log; chmod 777 vtigercrm.log"
	@docker-compose up -d

prod: init
	@docker-compose -f docker-compose.yml up -d --remove-orphans

start: prod
	@echo ">>> Myddleware is ready."

recreate: init
	@docker-compose -f docker-compose.yml up -d --remove-orphans --force-recreate

restart: recreate
	@echo ">>> Myddleware is ready."

fix:
	@docker-compose -f docker-compose.yml run --rm myddleware chmod 777 -R var/cache/ var/logs/ || true

bash:
	@docker-compose -f docker-compose.yml exec myddleware bash

docker-stop-all:
	@docker stop $$(docker ps -qa | grep -v $${MAKEBAT_CONTAINER_ID:-null}) >/dev/null 2>/dev/null || true

reset: clean
	@echo "===> Stai per cancellare tutto (digita: YES)?: " && \
		read AGREE && [ "$${AGREE}" = "YES" ] && docker-compose down -v --remove-orphans

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
