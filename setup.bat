@echo off

rem Clean-up project
docker-compose down -v
docker-compose pull --include-deps
docker-compose build myddleware
git reset --hard

