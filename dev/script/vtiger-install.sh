#!/bin/bash
set -e

## Error handling
error() { echo -e "\nERROR! Install fail on line $1."; }
trap 'error ${LINENO}' ERR

## Load environment
[[ -f .env ]] && source .env
if [[ -e "${gitlab_private_token}" ]]; then
  echo "Ignore Opencrmitalia installer."
  exit 0
fi

## Wait vtiger startup operations
if [[ -e .vtiger.lock ]]; then
    echo ">>> Waiting database preparation, it could take a few minutes"
    while [[ -f .vtiger.lock ]]; do echo -n "."; sleep 2; done; echo "."
fi

## Settings
WORKDIR=${PWD}
MODULES_LOCAL=${WORKDIR}/modules_local
PRIVATE_TOKEN=${gitlab_private_token}
PRIVATE_REGISTRY=https://gitlab.com/api/v4/projects/opencrmitalia%2F

## Download zip file from gitlab and prepare local module and layout
## Argument:
##   - $1 module name
##   - $2 branch name
##   - $3 layout name
## Output
##   - file modules_dev/$1.zip with module inside
download_gitlab_module () {
    local tmp_pwd="${PWD}"
    local module_tmp_dir=$(mktemp -d -t module-${1}-${2}-XXXXXX)
    local module_tmp_file=$(mktemp ${module_tmp_dir}/module-${1}-${2}-XXXXXX.zip)
    local module_file=${MODULES_LOCAL}/${1}.zip

    download_gitlab_archive "$1" "$2" "${module_tmp_file}" && true

    if [[ "$?" -eq 22 ]]; then
        [[ "$2" = "master" ]] && exit_with_error "Branch 'master' no longer supported, replace it with '$1:main' on file 'opencrmitalia_modules'."
        error_module_not_found "$1" "$2"
    fi

    cd ${module_tmp_dir}
    chmod 777 "${module_tmp_file}"
    echo -n "TMPFILE: " && ls -lh "${module_tmp_file}"
    local module_root_dir=$(unzip -qql "${module_tmp_file}" | head -n1 | tr -s ' ' | cut -d' ' -f5-)
    echo "ROOTDIR: ${module_root_dir}"
    unzip -qq -o "${module_tmp_file}"
    cd ${module_root_dir}
    zip -qq -r ${1}.zip . -x "cdn/*"
    mkdir -p ${MODULES_LOCAL}/layouts
    mv "${1}.zip" "${module_file}"
    if [[ -n "${3}" ]] && [[ -d "cdn/${3}" ]]; then
        cd "cdn/${3}"
        zip -qq -ro "${3}.zip" ./
        mv "${3}.zip" "${MODULES_LOCAL}/layouts/"
    fi
    chmod 777 -R ${MODULES_LOCAL}
    rm -fr "${module_tmp_dir}"
    cd "${tmp_pwd}"
}

## Retrieve the first sub directory under modules directory
## Argument:
##   - $1 module zip file
## Output
##   - sub directory name
get_module_dir_name () {
    unzip -qql "${1}" | grep -e "[[:space:]]modules/[[:alnum:]]" | head -n1 | cut -d'/' -f2
}

## Download and prepare zip file of a module from git
## Argument:
##   - $1 module name
##   - $2 branch name
##   - $3 layout name
## Output
##   - file $1.zip with module inside
install_gitlab_module () {
    local module_file="${MODULES_LOCAL}/${module}.zip"

    if [[ "$2" = "local" ]] ; then
        if [[ -f "${module_file}" ]]; then
            echo "NOTICE: Installing module from local!"
        else
            download_gitlab_module "$1" "main" "$3"
        fi
    else
        download_gitlab_module "$1" "$2" "$3"
    fi

    php -f /var/www/html/vtlib/tools/console.php -- --import="${module_file}" && true
    php -f /var/www/html/vtlib/tools/console.php -- --update="${module_file}" && true

    local module_dir_name=$(get_module_dir_name "${module_file}")

    chown www-data:www-data -R /var/www/html/modules/${module_dir_name}/

    if [[ -n "${3}" ]]; then
        local layout_file=${MODULES_LOCAL}/layouts/${3}.zip
        echo ">>> Installing '${3}' theme . . ."
        install_zip_file "${layout_file}" "/var/www/html/layouts/${3}/"
        echo "Updating ...DONE."
    fi
}

## Download and prepare zip file of Responsive Filemanager
## Argument:
##   - $1 module name
##   - $2 branch name
##   - $3 source name
## Output
##   - file $1.zip with module inside
install_responsive_filemanager () {
    local tmp_pwd="${PWD}"
    local module_tmp_dir=$(mktemp -d -t module-${1}-${2}-XXXXXX)
    local module_tmp_file=$(mktemp ${module_tmp_dir}/module-responsive-filemanager-XXXXXX.zip)
    local module_file=${MODULES_LOCAL}/responsive-filemanager.zip

    case "$3" in
        github)
            local source_url="https://github.com/trippo/ResponsiveFilemanager/releases/download/${2}/responsive_filemanager.zip"
            ;;
        cdn|*)
            local source_url="https://cdn.opencrmitalia.com/extensions/responsive/${2}/filemanager.zip"
            ;;
    esac

    curl -o "${module_tmp_file}" -fSL "${source_url}"

    echo -n "TMPFILE: " && ls -lh "${module_tmp_file}"
    cd "${module_tmp_dir}"
    unzip -qq -o "${module_tmp_file}"
    zip -qq -ro filemanager.zip filemanager
    mkdir -p ${MODULES_LOCAL}
    mv filemanager.zip "${module_file}"
    chmod 777 -R ${MODULES_LOCAL}
    rm -fr "${module_tmp_dir}"
    cd "${tmp_pwd}"

    install_zip_file "${module_file}" /var/www/html/

    echo "Updating ...DONE."
}

## Extract zip file content into destination and remove zip file
## Argument:
##   - $1 module zip file
##   - $2 destination directory
## Output
##   - all zip content placed into $2
install_zip_file () {
    local tmp_pwd="${PWD}"
    mkdir -p "${2}"
    cd "${2}"
    unzip -qq -o "${1}"
    cd "${tmp_pwd}"
}

## Download archive.zip from gitlab repository via API.
## Argument:
##   - $1 repository name
##   - $2 branch name
##   - $3 file on the disk
## Output
##   - downloaded file with name of $3
download_gitlab_archive () {
    curl -o "${3}" \
         -H "PRIVATE-TOKEN: ${PRIVATE_TOKEN}" \
         -fSL "${PRIVATE_REGISTRY}${1}/repository/archive.zip?sha=${2}"
}

## Show error message
## Argument:
##   - $1 error message
## Output
##   - show the message and exit
exit_with_error () {
    echo ""
    echo "ERROR: $1"
    exit 1
}

## Show error message
## Argument:
##   - $1 module name
##   - $2 branch or version name
## Output
##   - show the message and exit
error_module_not_found () {
    echo ""
    echo "-----------------------------"
    echo ">> ERROR: MODULE NOT FOUND <<"
    echo "-----------------------------"
    echo "Follow this special instructions:"
    echo " -> Contact the Opencrmitalia Dev Team for support on module '${1}' at version '${2}'"
    echo " -> You will receive the following file '${1}.zip', copy it into 'modules_local/' directory."
    echo " -> Continue with the following command: 'make install'"
    echo ""
    exit 1
}

# Chown config.inc.php
chown www-data:www-data /var/www/html/config.inc.php

## Dependencies install
if [[ -e opencrmitalia_modules ]]; then
    while IFS= read line || [[ -n "${line}" ]]; do
        [[ -z "${line}" ]] && continue
        [[ "${line::1}" == "#" ]] && continue

        cd ${WORKDIR}
        module=$(echo $line | cut -f1 -d:)
        branch=$(echo $line | cut -f2 -d:)

        echo ">>> Installing '${module}' from branch '${branch}' . . ."

        case "${module}" in
            suite-opencrmitalia)
                ## Install module and responsive template into layout directory
                install_gitlab_module "${module}" "${branch}" "responsive"
                ;;

            responsive-filemanager)
                ## Install responsive filemanager from github (not used for now)
                install_responsive_filemanager "${module}" "${branch}" "github"
                ;;

            cdn-filemanager|responsive-filemanager@cdn)
                ## Install responsive filemanager from cdn
                install_responsive_filemanager "${module}" "${branch}" "cdn"
                ;;

            *)  ## Install standard or extension modules
                install_gitlab_module "${module}" "${branch}"
                ;;
        esac
    done < opencrmitalia_modules
fi

## Back to work directory
cd ${WORKDIR}

## Auto-install
if [ -f manifest.xml ]; then
    echo ">>> Auto-install . . ."
    zip -qq -r module.zip manifest.xml languages
    php -f /var/www/html/vtlib/tools/console.php -- --import=/app/module.zip && true
    php -f /var/www/html/vtlib/tools/console.php -- --update=/app/module.zip && true
    rm -f module.zip
fi

## Temp Fix Permission
echo ">>> Clean-up and fix . . ."
#MODULE_NAME=$(cat manifest.xml | grep '<name>' | head -1 | tail -1 | sed -e 's/<[^>]*>//g' | tr -d ' \r\n')
#php -r "chdir('/var/www/html');set_include_path('/var/www/html');echo '1';require_once('vtlib/Vtiger/Layout.php'); require_once 'include/utils/VtlibUtils.php';echo '2';var_dump(vtlib_toggleModuleAccess(trim('${MODULE_NAME}'), false));var_dump(vtlib_toggleModuleAccess(trim('${MODULE_NAME}'), true));echo 'Ok!';"
chown www-data:www-data -RL /var/lib/vtiger
echo "Updating ...DONE."

## Prepare test cases
[[ -f /app/testcases/prepare.php ]] && php -f /app/testcases/prepare.php
[[ -f /app/testcases/admin.json ]] && chmod 777 /app/testcases/admin.json

echo ""
echo "Done."
