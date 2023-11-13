#!/usr/bin/env bash
#
# Exports the MySQL database.
#
# (c) Pere Orga Esteve <pere@orga.cat>
#
# This source file is subject to the AGPL license that is bundled with this
# source code in the file LICENSE.

set -e

cd "$(dirname "$0")/.."

##############################################################################
# Shows the help of this command.
# Arguments:
#   None
##############################################################################
usage() {
    echo "Usage: ./$(basename "$0")"
}

if [[ -n $1 ]]; then
    usage
    exit 1
fi

# If MYSQL_ROOT_PASSWORD variable is not set, load it from the .env file.
if [[ -z ${MYSQL_ROOT_PASSWORD} ]]; then
    export "$(grep 'MYSQL_ROOT_PASSWORD=' .env | xargs)"
    if [[ -z ${MYSQL_ROOT_PASSWORD} ]]; then
        echo "ERROR: MYSQL_ROOT_PASSWORD variable is not set." >&2
        exit 255
    fi
fi

readonly MYSQL_ROOT_PASSWORD

# TODO: Add compatibility with mariadb-dump.
docker exec pccd-mysql /usr/bin/mysqldump -uroot -p"${MYSQL_ROOT_PASSWORD}" --no-data --skip-dump-date pccd > tmp/schema.sql
docker exec pccd-mysql /usr/bin/mysqldump -uroot -p"${MYSQL_ROOT_PASSWORD}" --skip-dump-date --ignore-table=pccd.commonvoice pccd > install/db/db.sql
