#!/bin/bash

git add .
git commit -am "deploy on staging"
git push

ansible-playbook -i hosts.yml -l uat deploy.yml
