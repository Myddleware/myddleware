#!make

down:
	@docker-compose down --remove-orphans

debug: down
	@docker-compose up -d

prepare-demo:


prod: down
	@docker-compose -f docker-compose.yml up -d
