#!/bin/bash
set -e

git add .
git commit -am "stash changes before fetch upstream"

git checkout hotfix
git pull https://github.com/Myddleware/myddleware.git hotfix

git checkout master
git pull https://github.com/Myddleware/myddleware.git master

git checkout stable
