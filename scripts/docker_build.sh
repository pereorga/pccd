#!/usr/bin/env bash
#
# Deletes previous volumes, runs `docker-compose build` with passed arguments and executes `docker-compose up`.
#
# (c) Pere Orga Esteve <pere@orga.cat>
#
# This source file is subject to the AGPL license that is bundled with this
# source code in the file LICENSE.

set -e

##############################################################################
# Shows the help of this command.
# Arguments:
#   None
##############################################################################
usage() {
    echo "Usage: $(basename "$0") [OPTIONS]"
    echo ""
    echo "Optional arguments:"
    echo "  OPTIONS               The options to pass to docker-compose build command"
}

(cd "$(dirname "$0")/.." &&
    docker-compose down --volumes &&
    docker-compose build "$@" &&
    docker-compose up)
