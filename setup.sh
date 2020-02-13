#!/bin/bash

## Install dependancies
docker-compose run --rm myddleware php composer.phar install --ignore-platform-reqs --no-scripts
docker-compose run --rm myddleware php composer.phar update --ignore-platform-reqs --no-scripts

## Remove old settings
rm app/config/parameters.yml
rm app/config/public/parameters_public.yml
rm app/config/public/parameters_smtp.yml

## Prepare files and directories
docker-compose run --rm myddleware php composer.phar run-script post-install-cmd

## Prepare database
docker-compose run --rm myddleware ./prepare-database.sh

## Start all containers
docker-compose up -d
