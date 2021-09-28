#!/usr/bin/env bash

# Usage: migrate.sh [<work-dir>]
cd ${1:-"."}

bin/console doctrine:schema:update --no-interaction --force
