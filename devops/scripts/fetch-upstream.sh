#!/bin/bash
set -e

## Test 2
git add .
git commit -am "stash changes before fetch upstream" && true

git checkout hotfix
git pull https://github.com/Myddleware/myddleware.git hotfix
git push

git checkout master
git pull https://github.com/Myddleware/myddleware.git master
git push

git checkout contribute
git pull https://github.com/Myddleware/myddleware.git master
git push

git checkout develop
