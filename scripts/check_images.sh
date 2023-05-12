#!/usr/bin/env bash
#
# Checks image extensions and formats.
# This script usually takes a few minutes, unless a lot of new images are added. In that case, it can take a long time.
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

echo "Cobertes" > ../tmp/test_imatges_extensions.txt
echo "=============================" >> ../tmp/test_imatges_extensions.txt
for f in ../src/images/cobertes/*.jpg; do
    filetype=$(file -0 "${f}" | cut -d ':' -f2 | cut -d" " -f2)
    if [[ ${filetype} != "JPEG" ]]; then
        if [[ ${filetype} == "RIFF" ]]; then
            filetype="WEBP"
        fi
        filename=$(echo "${f}" | rev | cut -d '/' -f1 | rev)
        echo "${filename} is ${filetype}" >> ../tmp/test_imatges_extensions.txt
    fi
done
for f in ../src/images/cobertes/*.png; do
    filetype=$(file -0 "${f}" | cut -d ':' -f2 | cut -d" " -f2)
    if [[ ${filetype} != "PNG" ]]; then
        if [[ ${filetype} == "RIFF" ]]; then
            filetype="WEBP"
        fi
        filename=$(echo "${f}" | rev | cut -d '/' -f1 | rev)
        echo "${filename} is ${filetype}" >> ../tmp/test_imatges_extensions.txt
    fi
done
for f in ../src/images/cobertes/*.gif; do
    filetype=$(file -0 "${f}" | cut -d ':' -f2 | cut -d" " -f2)
    if [[ ${filetype} != "GIF" ]]; then
        if [[ ${filetype} == "RIFF" ]]; then
            filetype="WEBP"
        fi
        filename=$(echo "${f}" | rev | cut -d '/' -f1 | rev)
        echo "${filename} is ${filetype}" >> ../tmp/test_imatges_extensions.txt
    fi
done
echo "=============================" >> ../tmp/test_imatges_extensions.txt
echo "" >> ../tmp/test_imatges_extensions.txt
echo "" >> ../tmp/test_imatges_extensions.txt

echo "Imatges" >> ../tmp/test_imatges_extensions.txt
echo "=============================" >> ../tmp/test_imatges_extensions.txt
for f in ../src/images/paremies/*.jpg; do
    filetype=$(file -0 "${f}" | cut -d ':' -f2 | cut -d" " -f2)
    if [[ ${filetype} != "JPEG" ]]; then
        if [[ ${filetype} == "RIFF" ]]; then
            filetype="WEBP"
        fi
        filename=$(echo "${f}" | rev | cut -d '/' -f1 | rev)
        echo "${filename} is ${filetype}" >> ../tmp/test_imatges_extensions.txt
    fi
done
for f in ../src/images/paremies/*.png; do
    filetype=$(file -0 "${f}" | cut -d ':' -f2 | cut -d" " -f2)
    if [[ ${filetype} != "PNG" ]]; then
        if [[ ${filetype} == "RIFF" ]]; then
            filetype="WEBP"
        fi
        filename=$(echo "${f}" | rev | cut -d '/' -f1 | rev)
        echo "${filename} is ${filetype}" >> ../tmp/test_imatges_extensions.txt
    fi
done
for f in ../src/images/paremies/*.gif; do
    filetype=$(file -0 "${f}" | cut -d ':' -f2 | cut -d" " -f2)
    if [[ ${filetype} != "GIF" ]]; then
        if [[ ${filetype} == "RIFF" ]]; then
            filetype="WEBP"
        fi
        filename=$(echo "${f}" | rev | cut -d '/' -f1 | rev)
        echo "${filename} is ${filetype}" >> ../tmp/test_imatges_extensions.txt
    fi
done

# Check image formats using jpeginfo, pngcheck and gifsicle.
# We use set +e to ignore potential errors in these commands.
set +e
jpeginfo -c ../src/images/cobertes/*.jpg | grep -F 'ERROR' | grep -F -v 'OK' > ../tmp/test_imatges_format.txt
jpeginfo -c ../src/images/paremies/*.jpg | grep -F 'ERROR' | grep -F -v 'OK' >> ../tmp/test_imatges_format.txt
pngcheck ../src/images/cobertes/*.png ../src/images/paremies/*.png | grep -v 'OK:' >> ../tmp/test_imatges_format.txt
gifsicle --info ../src/images/cobertes/*.gif ../src/images/paremies/*.gif 2>> ../tmp/test_imatges_format.txt
set -e
