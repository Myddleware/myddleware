#!/bin/bash
set -e

[[ -d "CLONE" ]] || git clone https://github.com/opencrmitalia-official/myddleware.git CLONE

cd CLONE

#git pull https://github.com/Myddleware/myddleware.git Release-2.5.7 -X theirs --no-edit

git push origin --delete hotfix || true
git checkout master
git checkout -b hotfix
git pull https://github.com/Myddleware/myddleware.git hotfix -X theirs --no-edit
git push --set-upstream origin hotfix

git checkout master
git pull https://github.com/Myddleware/myddleware.git master -X theirs --no-edit
git push

git checkout contribute
git pull https://github.com/Myddleware/myddleware.git master -X theirs --no-edit
git push

cd ..
rm -fr CLONE



