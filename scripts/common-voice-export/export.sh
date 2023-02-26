#!/usr/bin/env bash
#
# Common Voice export.
# This script takes around 3 minutes to complete.
#
# (c) Pere Orga Esteve <pere@orga.cat>
#
# This source file is subject to the AGPL license that is bundled with this
# source code in the file LICENSE.

set -e

cd "$(dirname "$0")"

##############################################################################
# Shows the help of this command.
# Arguments:
#   None
##############################################################################
usage() {
    echo "Usage: $(basename "$0")"
    echo "Export Common Voice sentences."
}

if [[ -n $1 ]]; then
    usage
    exit 1
fi

docker exec pccd-web php scripts/common-voice-export/app.php > all.txt
rm -rf third_party/
mkdir third_party
git clone --depth=1 https://github.com/pereorga/pccd-lt-filter.git third_party/pccd-lt-filter
(cd third_party/pccd-lt-filter &&
    mvn package &&
    java -jar target/lt-filter-0.0.1-jar-with-dependencies.jar ../../all.txt > ../../pccd.txt 2> ../../error.txt)
grep -v -F 'SLF4J:' error.txt > excluded.txt
rm error.txt
