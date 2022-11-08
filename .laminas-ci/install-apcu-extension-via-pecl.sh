#!/bin/bash

PHP_VERSION="$1"

if ! [[ "${PHP_VERSION}" =~ 8\.2 ]]; then
  echo "APCu is only installed from pecl for PHP 8.2, ${PHP_VERSION} detected."
  exit 0;
fi

set +e

pecl install --configureoptions 'enable-apcu-debug="no"' apcu
echo "extension=apcu.so" > /etc/php/${PHP_VERSION}/mods-available/apcu.ini
phpenmod -v ${PHP} -s cli apcu
