#!/bin/bash

## Install dependancies
docker-compose run --rm myddleware php composer.phar install --ignore-platform-reqs --no-scripts

## Prepare files and directories
docker-compose run --rm myddleware php composer.phar run-script post-install-cmd

## Start all containers
docker-compose up -d
