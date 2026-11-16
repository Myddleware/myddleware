#!/usr/bin/env bash

## Setup database then create admin user
# Migrations first: they convert data the schema update cannot handle (e.g. 4.3 -> 4.4 JSON columns)
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || exit 1
php bin/console doctrine:schema:update --force
php bin/console doctrine:fixtures:load --append
php bin/console myddleware:add-user admin secret docker@myddleware.com || true

## Old code to promote to ROLE_SUPER_ADMIN
#php bin/console fos:user:promote admin ROLE_ADMIN
#php bin/console fos:user:promote admin ROLE_SUPER_ADMIN -e prod
