#!make

init:
	@[ -f hosts ] || touch hosts
	@[ -f .env ] || cp .env.example .env
	@cd var/databases && [ -f filebrowser.db ] || cp filebrowser.db.empty filebrowser.db

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
	@docker-compose -f docker-compose.yml up -d --remove-orphans

down:
	@docker-compose down --remove-orphans

build:
	@docker-compose build myddleware

install: init up
	@docker-compose -f docker-compose.yml run --rm myddleware php composer.phar install --ignore-platform-reqs --no-scripts
	@echo "Install done."

update: init build up
	@docker-compose -f docker-compose.yml run --rm myddleware rm -fr var/cache/* vendor
	@docker-compose -f docker-compose.yml run --rm myddleware php composer.phar install --ignore-platform-reqs --no-scripts
	@echo "Update done."

require-vtiger-client:
	@docker-compose -f docker-compose.yml run --rm myddleware php composer.phar require javanile/vtiger-client:0.0.21 -vvvv --ignore-platform-reqs

setup-files:
	@docker-compose -f docker-compose.yml run --rm myddleware php composer.phar run-script post-install-cmd
	@docker-compose -f docker-compose.yml run --rm myddleware chmod 777 -R var/cache var/logs

setup-database: up sleep
	@docker-compose -f docker-compose.yml exec myddleware bash prepare-database.sh

setup: setup-files setup-database
	@echo "Setup Myddleware files and database: OK!"

logs: debug
	@docker-compose logs -f myddleware

debug: init
	@docker-compose -f docker-compose.yml up -d --remove-orphans

dev: down
	@docker-compose run --rm myddleware rm -fr var/logs/vtigercrm.log
	@docker-compose up -d

prod: down
	@docker-compose -f docker-compose.yml up -d

bash:
	@docker-compose -f docker-compose.yml exec myddleware bash

reset: clean
	@echo "===> Stai per cancellare tutto (digita: YES)?: " && \
		read AGREE && [ "$${AGREE}" = "YES" ] && docker-compose down -v --remove-orphans

## -------
## Testing
## -------
test-debug: reset install setup debug
	@docker-compose ps

test-dev: reset install setup dev
	@docker-compose ps

test-prod: reset install setup prod
	@docker-compose ps

test-backup: up
	@docker-compose -f docker-compose.yml logs -f backup
