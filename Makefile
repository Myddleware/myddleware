#!make

init:
	@cd var/databases && [ -f filebrowser.db ] || cp filebrowser.db.empty filebrowser.db

ps:
	@docker-compose ps

up:
	@docker-compose up -d

down:
	@docker-compose down --remove-orphans

install:
	composer install

debug: down
	@docker-compose up -d

prepare-demo:


prod: down
	@docker-compose -f docker-compose.yml up -d
