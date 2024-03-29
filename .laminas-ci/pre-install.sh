#!/bin/bash

WORKING_DIRECTORY=$2
JOB=$3
PHP_VERSION=$(php -nr "echo PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;")

if [ ! -z "$GITHUB_BASE_REF" ] && [[ "$GITHUB_BASE_REF" =~ ^[0-9]+\.[0-9] ]]; then
  readarray -td. TARGET_BRANCH_VERSION_PARTS <<<"${GITHUB_BASE_REF}.";
  unset 'TARGET_BRANCH_VERSION_PARTS[-1]';
  declare -a TARGET_BRANCH_VERSION_PARTS
  MAJOR_OF_TARGET_BRANCH=${TARGET_BRANCH_VERSION_PARTS[0]}
  MINOR_OF_TARGET_BRANCH=${TARGET_BRANCH_VERSION_PARTS[1]}

  export COMPOSER_ROOT_VERISON="${MAJOR_OF_TARGET_BRANCH}.${MINOR_OF_TARGET_BRANCH}.99"
  echo "Exported COMPOSER_ROOT_VERISON as ${COMPOSER_ROOT_VERISON}"
fi
