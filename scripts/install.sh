#!/usr/bin/env bash
#
# Runs PHP installation script and creates the sitemaps.
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
    echo "Usage: ./$(basename "$0")"
}

if [[ -n $1 ]]; then
    usage
    exit 1
fi

if [[ -f /.dockerenv ]]; then
    echo "Detected running inside Docker..."
    IS_DOCKER=1
else
    echo "Running outside Docker..."
    IS_DOCKER=0
fi

echo "Running installation script..."
if [[ "${IS_DOCKER}" -eq 1 ]]; then
    php install.php
else
    docker exec pccd-web php scripts/install.php
fi

echo "Building sitemaps and robots.txt..."
echo "User-agent: *" > ../docroot/robots.txt
echo "Disallow:" >> ../docroot/robots.txt

if [[ "${IS_DOCKER}" -eq 1 ]]; then
    php build_sitemap.php > ../docroot/sitemap.txt
else
    docker exec pccd-web php scripts/build_sitemap.php > ../docroot/sitemap.txt
fi

# Split the sitemap in multiple files, to overcome a Google limit.
split -d -l 49999 ../docroot/sitemap.txt sitemap_
for i in sitemap_*; do
    mv "${i}" "../docroot/${i}.txt"
    echo "Sitemap: https://pccd.dites.cat/${i}.txt" >> ../docroot/robots.txt
done

# Store last updated date.
# sed is necessary on macOS to add apostrophes to the date.
LC_TIME='ca_ES' date | cut -d"," -f2 | sed "s/de o/d'o/" | sed "s/de a/d'a/" > ../tmp/db_date.txt
