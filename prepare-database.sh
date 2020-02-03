#!/bin/bash

php bin/console d:s:u --force
php bin/console d:f:l --append
php bin/console fos:user:create admin admin@example.com admin
php bin/console fos:user:promote admin ROLE_ADMIN
