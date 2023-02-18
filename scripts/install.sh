#!/usr/bin/env bash
#
# Runs PHP installation script and create sitemap.
# This script needs to be executed after the database is converted, with the website running.
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
    echo "Installs the PCCD website and creates the sitemap files."
}

if [[ -n $1 ]]; then
    usage
    exit 1
fi

echo "Running installation script..."
docker exec pccd-web php scripts/install.php

echo "Building sitemaps..."
docker exec pccd-web php scripts/build_sitemap.php > ../docroot/sitemap_all.txt

# Split the sitemap in multiple files, to overcome a Google limit.
split -l 49999 ../docroot/sitemap_all.txt
mv xaa ../docroot/sitemap.txt
mv xab ../docroot/sitemap2.txt

# Store last updated date.
# sed is necessary on macOS to add apostrophes to the date.
LC_TIME='ca_ES' date | cut -d"," -f2 | sed "s/de o/d'o/" | sed "s/de a/d'a/" > ../tmp/date.txt
