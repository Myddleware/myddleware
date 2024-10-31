#!/usr/bin/env bash

git pull origin release-3.4.0a
composer install
nvm install 16
yarn install
yarn build
php bin/console doctrine:schema:update --force
 
rm -rf var/cache/background/*
 
rm -rf var/cache/dev/*