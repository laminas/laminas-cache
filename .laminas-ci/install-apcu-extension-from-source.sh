#!/bin/bash

PHP_VERSION="$1"

if ! [[ "${PHP_VERSION}" =~ 8\.1 ]]; then
  echo "APCu is only installed from source for PHP 8.1, ${PHP_VERSION} detected."
  exit 0;
fi

set +e

CURRENT_WORKING_DIRECTORY=$(pwd)

cd $TMPDIR
git clone https://github.com/krakjoe/apcu.git
cd apcu

phpize
./configure
make
make install

cd $CURRENT_WORKING_DIRECTORY

