#!/usr/bin/env bash
#
# Checks the expiration date in the production certificate.
#
# URL of the environment can be passed as argument:
#   ./check_certificate.sh https://pccd.dites.cat
#
# Otherwise, https://pccd.dites.cat is used as default.
#
# (c) Pere Orga Esteve <pere@orga.cat>
#
# This source file is subject to the AGPL license that is bundled with this
# source code in the file LICENSE.

set -e

shopt -s expand_aliases

cd "$(dirname "$0")/.."

if [[ -z $1 ]]; then
    REMOTE_ENVIRONMENT_URL="https://pccd.dites.cat"
else
    REMOTE_ENVIRONMENT_URL="$1"
fi
readonly REMOTE_ENVIRONMENT_URL
export REMOTE_ENVIRONMENT_URL

##############################################################################
# Shows the help of this command.
# Arguments:
#   None
##############################################################################
usage() {
    echo "Usage: $(basename "$0") [--help] [ENVIRONMENT_URL]"
    echo "Check the expiration date of the production certificate."
    echo ""
    echo "  --help"
    echo "    Show this help and exit"
    echo "  ENVIRONMENT_URL       The website URL, without trailing slash (default: https://pccd.dites.cat)"
}

if [[ -n $2 ]]; then
    usage
    exit 1
fi

if [[ $1 == "--help" ]]; then
    usage
    exit 0
fi

# Call gdate, if it is available.
if command -v gdate > /dev/null; then
    alias date=gdate
fi

# Get the expiration date of the certificate with curl.
EXPIRATION_DATE=$(curl -v -I --stderr - "${REMOTE_ENVIRONMENT_URL}" | grep "expire date" | cut -d ":" -f 2- | xargs)

# Convert the expiration date to seconds, using GNU date.
EXPIRATION_DATE_SECONDS=$(date -d "${EXPIRATION_DATE}" +%s)

# Get the current date.
CURRENT_DATE=$(date -d "$(date +%Y-%m-%d)" +%s)

# Calculate the difference in days.
EXPIRATION_DATE_DAYS=$(((EXPIRATION_DATE_SECONDS - CURRENT_DATE) / 86400))

# Exit with error if the certificate expires in less than 10 days.
if [[ ${EXPIRATION_DATE_DAYS} -lt 10 ]]; then
    RED='\033[0;31m'
    NC='\033[0m'
    echo -e "${RED}${REMOTE_ENVIRONMENT_URL} certificate expires in ${EXPIRATION_DATE_DAYS} days.${NC}"
    exit 1
else
    GREEN='\033[0;32m'
    NC='\033[0m'
    echo -e "${GREEN}${REMOTE_ENVIRONMENT_URL} certificate expires in ${EXPIRATION_DATE_DAYS} days.${NC}"
fi
