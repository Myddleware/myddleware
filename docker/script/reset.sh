#!/usr/bin/env bash

echo "===> You are about to erase everything (type in capital letter: YES)?: "

read AGREE

if [ "${AGREE}" = "YES" ]; then
  docker-compose down -v --remove-orphans
  docker-compose run --rm --no-deps myddleware rm -fr vendor var/cache/prod
  docker-compose run --rm --no-deps myddleware chmod -R 777 docker/tmp
  git clean -dfx
else
  echo "Wrong response!"
fi
