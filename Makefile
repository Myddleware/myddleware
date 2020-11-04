#!make

init:
	@cd var/databases && [ -f filebrowser.db ] || cp filebrowser.db.empty filebrowser.db

clean:
	@rm -fr .git/.idea >/dev/null 2>/dev/null || true
	@mv .idea .git/.idea >/dev/null 2>/dev/null || true
	@git clean -dfx -e \!.idea -e \!modules_local || true
	@mv .git/.idea .idea >/dev/null 2>/dev/null || true

sleep:
	@sleep 5

ps:
	@docker-compose ps

up:
	@docker-compose up -d

down:
	@docker-compose down --remove-orphans

install:
	@docker-compose -f docker-compose.yml run --rm myddleware php composer.phar install --ignore-platform-reqs --no-scripts

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
