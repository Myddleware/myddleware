#!make

init:
	@cd var/databases && [ -f filebrowser.db ] || cp filebrowser.db.empty filebrowser.db

clean:
	@rm -fr .git/.idea >/dev/null 2>/dev/null || true
	@mv .idea .git/.idea >/dev/null 2>/dev/null || true
	@git clean -dfx >/dev/null 2>/dev/null || true
	@mv .git/.idea .idea >/dev/null 2>/dev/null || true

sleep:
	@sleep 5

ps:
	@docker-compose ps

up:
	@docker-compose -f docker-compose.yml up -d --remove-orphans

down:
	@docker-compose down --remove-orphans

install: init up
	@docker-compose -f docker-compose.yml run --rm myddleware php composer.phar install --ignore-platform-reqs --no-scripts

update: up
	@rm -fr var/cache/* vendor
	@docker-compose -f docker-compose.yml run --rm myddleware php composer.phar install --ignore-platform-reqs --no-scripts

require-vtiger-client:
	@docker-compose -f docker-compose.yml run --rm myddleware php composer.phar require javanile/vtiger-client:0.0.16 -vvvv --ignore-platform-reqs

setup-files:
	@docker-compose -f docker-compose.yml run --rm myddleware php composer.phar run-script post-install-cmd

setup-database: up sleep
	@docker-compose -f docker-compose.yml exec myddleware bash prepare-database.sh

setup: setup-files setup-database

debug: down
	@docker-compose up -d

dev:
	echo "DEV"

prod: down
	@docker-compose -f docker-compose.yml up -d

reset: clean
	@echo "===> Stai per cancellare tutto (digita: YES)?: " && \
		read AGREE && [ "$${AGREE}" = "YES" ] && docker-compose down -v --remove-orphans
