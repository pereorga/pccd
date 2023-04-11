#!/usr/bin/env bash
#
# Minifies the assets.
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
    echo "Usage: $(basename "$0")"
    echo "Build and minify the JS/CSS assets."
}

if [[ -n $1 ]]; then
    usage
    exit 1
fi

echo "Minifying src/js/app.js..."
echo "/*! Desenvolupat per Pere Orga <pere@orga.cat>, 2020. */" > docroot/js/script.min.js
npx terser src/js/app.js --compress --mangle >> docroot/js/script.min.js &

echo "Minifying src/css/base.css..."
# cssnano and csso are good too, but both lack a bit of updates, so clean-css is used.
npx cleancss -O2 src/css/base.css > docroot/css/base.min.css &
for file in src/css/pages/*.css; do
    echo "Minifying ${file}..."
    npx cleancss -O2 "${file}" > "docroot/css/$(basename "${file}" .css).min.css" &
done
