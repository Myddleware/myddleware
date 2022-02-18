#!/usr/bin/env bash

## Extend Hosts
echo "====[ UPDATE HOSTS ]===="
cat hosts >> /etc/hosts
cat /etc/hosts
echo "--"

## Start Apache
echo "====[ START APACHE ]===="
apache2-foreground "$@"
