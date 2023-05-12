#!/usr/bin/env bash
#
# Runs PHP background report generation.
#
# The following scripts generate reports of errors of multiple types.
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
}

if [[ -n $1 ]]; then
    usage
    exit 1
fi

./check_images.sh > /dev/null &
PID_LIST="$!"

docker exec pccd-web php scripts/background_tests.php llibres_urls > ../tmp/test_llibres_URL.txt &
PID_LIST+=" $!"
docker exec pccd-web php scripts/background_tests.php fonts_urls > ../tmp/test_fonts_URL.txt &
PID_LIST+=" $!"
docker exec pccd-web php scripts/background_tests.php imatges_urls > ../tmp/test_imatges_URL_IMATGE.txt &
PID_LIST+=" $!"
docker exec pccd-web php scripts/background_tests.php imatges_links > ../tmp/test_imatges_URL_ENLLAC.txt &
PID_LIST+=" $!"
docker exec pccd-web php scripts/background_tests.php paremiotipus_repetits 0 10000 > ../tmp/test_tmp_repetits_1.txt &
PID_LIST+=" $!"
docker exec pccd-web php scripts/background_tests.php paremiotipus_repetits 10000 25000 > ../tmp/test_tmp_repetits_2.txt &
PID_LIST+=" $!"
docker exec pccd-web php scripts/background_tests.php paremiotipus_repetits 25000 > ../tmp/test_tmp_repetits_3.txt &
PID_LIST+=" $!"

php image-convertor/small_images_reporter.php > ../tmp/test_imatges_petites.txt &
PID_LIST+=" $!"

trap 'kill "${PID_LIST}"' SIGINT

echo "Parallel processes (${PID_LIST}) have started"

# shellcheck disable=SC2086
wait ${PID_LIST}

cat ../tmp/test_tmp_repetits_*.txt > ../tmp/test_repetits.txt
git diff ../tmp/test_repetits.txt | grep -E '^\+' | grep -vF '++' | cut -c 2- > ../tmp/test_repetits_new.txt

echo
echo "All reports have been generated"
