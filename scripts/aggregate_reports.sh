#!/usr/bin/env bash
#
# Aggregates some reports output.
#
# (c) Pere Orga Esteve <pere@orga.cat>
#
# This source file is subject to the AGPL license that is bundled with this
# source code in the file LICENSE.

set -e

cd "$(dirname "$0")"

cat ../tmp/test_tmp_repetits_*.txt > ../tmp/test_repetits.txt
git diff ../tmp/test_repetits.txt | grep -E '^\+' | grep -vF '++' | cut -c 2- > ../tmp/test_repetits_new.txt

cat ../tmp/test_tmp_imatges_URL_ENLLAC_*.txt > ../tmp/test_imatges_URL_ENLLAC.txt

cat ../tmp/test_tmp_imatges_URL_IMATGE_*.txt > ../tmp/test_imatges_URL_IMATGE.txt
