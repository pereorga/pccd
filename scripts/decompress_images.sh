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
    echo "Usage: $(basename "$0")"
}

if [[ -n $1 ]]; then
    usage
    exit 1
fi

if [[ -f "Cobertes.zip" ]]; then
    rm -r src/images/cobertes
    unar -e IBM-850 Cobertes.zip
    mv Cobertes src/images/cobertes
    chmod 644 src/images/cobertes/*
else
    echo "Warning: Cobertes.zip not found"
fi

if [[ -f "Imatges.zip" ]]; then
    rm -r src/images/paremies
    unar -e IBM-850 Imatges.zip
    mv Imatges src/images/paremies
    chmod 644 src/images/paremies/*
else
    echo "Warning: Imatges.zip not found"
fi

if [[ -f "Obres-VPR.zip" ]]; then
    unar -e IBM-850 Obres-VPR.zip
    chmod 644 Obres-VPR/*
    mv -n Obres-VPR/* src/images/cobertes
    rm -r Obres-VPR
else
    echo "Warning: Obres-VPR.zip not found"
fi
