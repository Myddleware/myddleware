@echo off

rem Clean-up project
docker-compose down -v
docker-compose pull --include-deps
docker-compose build myddleware
git reset --hard

rem Install dependancies
docker-compose run --rm myddleware php -d memory_limit=4G composer.phar install --ignore-platform-reqs --no-scripts
docker-compose run --rm myddleware php -d memory_limit=4G composer.phar update --ignore-platform-reqs --no-scripts

rem Prepare database
docker-compose run --rm myddleware bash prepare-database.sh

rem Start all containers
docker-compose up -d
