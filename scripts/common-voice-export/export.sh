#!/usr/bin/env bash
#
# Common Voice export.
#
# Export sentences to be imported into Common Voice. This script takes around 3 minutes to complete.
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
    echo "Usage: ./$(basename "$0")"
}

if [[ -n $1 ]]; then
    usage
    exit 1
fi

# Clean up previous files.
rm -f filtered.txt controversial.txt

# Run export script, trying to filter out controversial sentences.
docker exec pccd-web php scripts/common-voice-export/app.php > filtered.txt 2> controversial.txt

# Exclude more sentences with LanguageTool.
# shellcheck disable=SC2016
(
    cd ../../vendor/pereorga/pccd-lt-filter &&
        mvn package &&
        VERSION=$(mvn -q -Dexec.executable="echo" -Dexec.args='${project.version}' --non-recursive exec:exec) &&
        java -jar target/lt-filter-"${VERSION}"-jar-with-dependencies.jar \
            ../../../scripts/common-voice-export/filtered.txt \
            > ../../../scripts/common-voice-export/pccd.txt \
            2> ../../../scripts/common-voice-export/error.txt
)

# Clean up filter output.
grep -v -F 'SLF4J:' error.txt > excluded.txt

# Get the new LT-excluded sentences since last commit.
git diff --unified=0 excluded.txt excluded.txt | grep -E '^\+[^+]' | sed 's/^\+//' > excluded_new_tmp.txt

# Only update the file if there are new entries.
if [[ "$(wc -l < excluded_new_tmp.txt)" -gt 1 ]]; then
    cp excluded_new_tmp.txt excluded_new.txt
fi

# Get the new PCCD sentences since last push to CV.
curl --fail --silent https://raw.githubusercontent.com/common-voice/common-voice/main/server/data/ca/pccd.txt > cv.txt
comm -23 <(sort pccd.txt) <(sort cv.txt) > pccd_new.txt

# Remove temporary files.
rm error.txt cv.txt filtered.txt excluded_new_tmp.txt
