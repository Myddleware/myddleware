#!/bin/bash
set -e

if [[ -z "$1" ]]; then
    echo "Syntax error: required target (es. ./connect.sh uat)"
    exit 1
fi

if [[ -z "$(command -v ansible-inventory)" ]]; then
    echo "System error: Ansible not found."
    exit 1
fi

host=$(ansible-inventory -i devops/scripts/.hosts.yml --host "$1")
working_dir=$(echo "${host}" | jq ".working_dir?" | grep -v '^null$' | tr -d \")
ansible_host=$(echo "${host}" | jq ".ansible_host?" | grep -v '^null$' | tr -d \")
ansible_user=$(echo "${host}" | jq ".ansible_user?" | grep -v '^null$' | tr -d \")
#ansible_password=$(echo "${host}" | jq ".ansible_password?" | grep -v '^null$' | tr -d \")
ansible_ssh_private_key_file=$(echo "${host}" | jq ".ansible_ssh_private_key_file?" | grep -v '^null$' | tr -d \")

#sshpass -p "${ansible_password}"
ssh -i "${ansible_ssh_private_key_file}" "${ansible_user}@${ansible_host}"
