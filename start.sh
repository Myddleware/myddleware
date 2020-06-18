#!/bin/bash

## Clean-up project
docker-compose down -v
docker-compose pull --include-deps
docker-compose build myddleware
git reset --hard

## Install dependancies
docker-compose run --rm myddleware php -d memory_limit=4G composer.phar install --ignore-platform-reqs --no-scripts
docker-compose run --rm myddleware php -d memory_limit=4G composer.phar update --ignore-platform-reqs --no-scripts

## Prepare files and directories
docker-compose run --rm myddleware php composer.phar run-script post-install-cmd

## Prepare database
docker-compose run --rm myddleware bash prepare-database.sh

## Start all containers
docker-compose up -d
