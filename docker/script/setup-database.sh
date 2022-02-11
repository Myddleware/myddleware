#!/usr/bin/env bash

## Setup database then create admin user
php bin/console doctrine:schema:update --force
php bin/console doctrine:fixtures:load --append
php bin/console myddleware:add-user admin secret docker@myddleware.com || true

## Old code to promote to ROLE_SUPER_ADMIN
#php bin/console fos:user:promote admin ROLE_ADMIN
#php bin/console fos:user:promote admin ROLE_SUPER_ADMIN -e prod
