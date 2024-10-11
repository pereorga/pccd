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

set -eu

cd "$(dirname "$0")"

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

# Remove temporary files.
rm error.txt filtered.txt
