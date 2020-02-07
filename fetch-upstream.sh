#!/bin/bash
set -e

git checkout hotfix
git pull https://github.com/Myddleware/myddleware.git hotfix

git checkout master
git pull https://github.com/Myddleware/myddleware.git master
