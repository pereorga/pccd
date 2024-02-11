#!/usr/bin/env bash
#
# Decompresses provided images in Cobertes.zip, Imatges.zip and Obres-VPR.zip files.
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

##############################################################################
# Handles the extraction, and file name normalization for a given .zip file.
# Arguments:
#   $1: Name of the zip file (without the .zip extension).
#   $2: Target directory to move the extracted files.
##############################################################################
function handle_zip_file {
    local -r zip_name="$1"
    local -r target_dir="$2"
    local normalized_name
    local os_name
    os_name="$(uname -s)"

    if [[ -f "${zip_name}.zip" ]]; then
        [[ -d ${target_dir} ]] && rm -r "${target_dir}"
        unar -f -e IBM-850 "${zip_name}.zip"
        mv "${zip_name}" "${target_dir}"
        chmod 644 "${target_dir}"/*

        # Normalize UTF-8 characters in the filename, like we do with the database contents. Apparently this is
        # necessary only on macOS filesystems. The may be related to unar (unzip command did bring other compatibility
        # issues with the encoding in some uploads). TODO: we may want to test 7zz.
        # TODO: Check convmv, iconv and other alternatives (or not, as using Bash does not add additional dependencies).
        if [[ ${os_name} == "Darwin" ]]; then
            # But not when using NIX.
            if [[ -z ${IN_NIX_SHELL} ]]; then
                find "${target_dir}" -type f | while read -r file; do
                    normalized_name=$(echo "${file}" | uconv -x nfkc)
                    if [[ ${file} != "${normalized_name}" ]]; then
                        mv -v "${file}" "${normalized_name}"
                    fi
                done
            fi
        fi
    else
        echo "Warning: ${zip_name}.zip not found"
    fi
}

if [[ -n $1 ]]; then
    usage
    exit 1
fi

handle_zip_file "Cobertes" "src/images/cobertes"
handle_zip_file "Imatges" "src/images/paremies"

# Merge author books with the cobertes.
if [[ -f Obres-VPR.zip ]]; then
    unar -f -e IBM-850 Obres-VPR.zip
    chmod 644 Obres-VPR/*
    mv -f Obres-VPR/* src/images/cobertes
    rm -r Obres-VPR
fi
