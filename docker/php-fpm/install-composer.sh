#!/bin/sh

#
# https://getcomposer.org/doc/faqs/how-to-install-composer-programmatically.md
#

EXPECTED_SIGNATURE=$(wget -q -O - https://composer.github.io/installer.sig)
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_SIGNATURE=$(php -r "echo hash_file('SHA384', 'composer-setup.php');")

if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]
then
    >&2 echo 'ERROR: Invalid installer signature'
    rm composer-setup.php
    exit 1
fi

echo "Installer signature is correct: $ACTUAL_SIGNATURE"

php composer-setup.php --version=2.2.3 && mv composer.phar /usr/local/bin/composer
RESULT=$?
rm composer-setup.php
exit $RESULT

# TODO: verify that commands above are correct since COmposer version has been upgraded: https://getcomposer.org/download/)