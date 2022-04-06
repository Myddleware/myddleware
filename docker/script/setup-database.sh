#!/usr/bin/env bash

## Setup database then create admin user
php bin/console doctrine:schema:update --force
php bin/console doctrine:fixtures:load --append
php bin/console myddleware:add-user admin secret docker@myddleware.com || true

