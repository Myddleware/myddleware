#!/usr/bin/env bash

echo " [1/6]===> Pulling the latest changes from the release-3.4.0a branch"
git pull origin release-3.4.0a

echo " [2/6]===> Installing the dependencies"
composer install

echo " [3/6]===> Installing the node dependencies"
nvm install 16

yarn install

echo " [4/6]===> Building the assets"
yarn build

echo " [5/6]===> Updating the database schema"
php bin/console doctrine:schema:update --force

echo " [6/6]===> Clearing the cache"
rm -rf var/cache/background/*

rm -rf var/cache/dev/*

echo "===> Deployment completed successfully"