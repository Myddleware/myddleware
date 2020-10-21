#!make

down:
	@docker-compose down --remove-orphans

debug: down
	@docker-compose up -d

prod: down
	@docker-compose -f docker-compose.yml up -d
