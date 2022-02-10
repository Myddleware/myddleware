#!/usr/bin/env bash

echo "===> You are about to erase everything (type in capital letter: YES)?: "

#read AGREE

#if [ "${AGREE}" = "YES" ]; then
  docker-compose down -v --remove-orphans
  docker-compose run --rm --no-deps myddleware rm -fr vendor node_modules var/cache/prod docker/var/mysql
  docker-compose run --rm --no-deps myddleware chmod -R 777 docker/tmp
  mkdir -p docker/var/mysql && touch docker/var/mysql/.gitkeep
  git clean -dfx
#else
#  echo "Wrong response!"
#fi
