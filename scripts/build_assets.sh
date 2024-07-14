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
    echo "Usage: ./$(basename "$0")"
}

if [[ -n $1 ]]; then
    usage
    exit 1
fi

echo "Minifying src/js/app.js..."
npx terser src/js/app.js --compress --mangle > docroot/js/app.min.js
sdt_version=$(jq -r '.dependencies["simple-datatables"]' package.json | sed 's/^\^//')
std_dislaimer="/*! This bundle includes simple-datatables ${sdt_version} (https://github.com/fiduswriter/simple-datatables). License: https://www.gnu.org/licenses/lgpl-3.0.html */"
for file in src/js/pages/*.js; do
    echo "Minifying ${file}..."
    if [[ "${file}" == "src/js/pages/fonts.js" ]]; then
        echo "${std_dislaimer}" > docroot/js/pages/fonts.min.js
        npx terser node_modules/simple-datatables/dist/umd/simple-datatables.js --compress --mangle >> docroot/js/pages/fonts.min.js
        npx terser src/js/pages/fonts.js --compress --mangle >> docroot/js/pages/fonts.min.js
    else
        npx terser "${file}" --compress --mangle > "docroot/js/pages/$(basename "${file}" .js).min.js"
    fi
done

echo "Minifying src/css/base.css..."
# cssnano and csso are good too, but both lack a bit of updates, so clean-css is used.
npx cleancss -O2 src/css/base.css > docroot/css/base.min.css
for file in src/css/pages/*.css; do
    echo "Minifying ${file}..."
    if [[ "${file}" == "src/css/pages/fonts.css" ]]; then
        echo "${std_dislaimer}" > docroot/css/pages/fonts.min.css
        npx cleancss -O2 node_modules/simple-datatables/dist/style.css >> docroot/css/pages/fonts.min.css
        npx cleancss -O2 "${file}" >> docroot/css/pages/fonts.min.css
    else
        npx cleancss -O2 "${file}" > "docroot/css/pages/$(basename "${file}" .css).min.css"
    fi
done

npm run export:asset-sizes
