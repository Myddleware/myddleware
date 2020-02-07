#!/bin/bash

## Install dependancies
docker-compose run --rm myddleware php composer.phar install --ignore-platform-reqs --no-scripts

## Start all containers
docker-compose up -d
