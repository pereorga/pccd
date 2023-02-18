#!/usr/bin/env bash
#
# Audits a running website with Google Lighthouse.
#
# URL of the environment can be passed as argument:
#   ./audit_website.sh http://localhost:8091
# Otherwise, http://localhost:8092 (default Apache port) is used as default.
#
# (c) Pere Orga Esteve <pere@orga.cat>
#
# This source file is subject to the AGPL license that is bundled with this
# source code in the file LICENSE.

set -e -o pipefail

cd "$(dirname "$0")"

##############################################################################
# Shows the help of this command.
# Arguments:
#   None
##############################################################################
usage() {
    echo "Usage: $(basename "$0") [ENVIRONMENT_URL]"
    echo "Run multiple tests and checks in the website."
    echo ""
    echo "  ENVIRONMENT_URL     The website URL, without trailing slash (default: http://localhost:8092)"
}

if [[ -n $2 ]]; then
    usage
    exit 1
fi

remote_environment_url="http://localhost:8092"
if [[ -n $1 ]]; then
    remote_environment_url="$1"
fi

readonly remote_environment_url

##############################################################################
# Audits URL using Google lighthouse.
# Arguments:
#   URL         The URL to validate
#   [DEVICE]    (optional) The browser mode ("mobile" or "desktop")
##############################################################################
audit_url() {
    local -r URL=$1
    local -r DEVICE=${2:-desktop}
    local -r CATEGORIES="accessibility,best-practices,performance,seo"
    local -r JSON_FILENAME=../tmp/lighthouse_audit.json

    echo ""
    if [[ ${DEVICE} == desktop ]]; then
        echo "Running Ligthouse categories ${CATEGORIES} on ${URL} (desktop)..."
        npx lighthouse "${URL}" --quiet --no-enable-error-reporting --only-categories="${CATEGORIES}" \
            --chrome-flags="--headless" --output=json > "${JSON_FILENAME}"
    else
        echo "Running Ligthouse categories ${CATEGORIES} on ${URL} (mobile)..."
        npx lighthouse "${URL}" --quiet --no-enable-error-reporting --screenEmulation.mobile \
            --screenEmulation.width=360 --screenEmulation.height=640 --screenEmulation.deviceScaleFactor=2 \
            --only-categories="${CATEGORIES}" --chrome-flags="--headless" --output=json > "${JSON_FILENAME}"
    fi

    for category in ${CATEGORIES//,/ }; do
        if [[ ${category} == *-* ]]; then
            # Escape hyphens.
            category="[\"${category}\"]"
        fi
        local score
        score=$(jq --raw-output ".categories | .${category} | .score" < "${JSON_FILENAME}")
        if [[ ${score} != 1 ]]; then
            if [[ ${DEVICE} == desktop ]]; then
                echo "ERROR: '${category}' score is less than 1 (${score}). Run 'npx lighthouse \"${URL}\" \
                    --only-categories=\"${CATEGORIES}\" --no-enable-error-reporting --view'" >&2
            else
                echo "ERROR: '${category}' score is less than 1 (${score}) on mobile. Run 'npx lighthouse \"${URL}\" \
                    --only-categories=\"${CATEGORIES}\" --no-enable-error-reporting --no-enable-error-reporting \
                    --screenEmulation.mobile --screenEmulation.width=360 --screenEmulation.height=640 \
                    --screenEmulation.deviceScaleFactor=2 --view'" >&2
            fi
            if [[ ${category} != "performance" ]]; then
                exit 255
            fi
        fi
    done

    echo "All essential audits score 100%."
}

audit_url "${remote_environment_url}/" desktop
audit_url "${remote_environment_url}/" mobile
audit_url "${remote_environment_url}/p/A_Agramunt_comerciants_i_a_T%C3%A0rrega_comediants" desktop
audit_url "${remote_environment_url}/p/A_Agramunt_comerciants_i_a_T%C3%A0rrega_comediants" mobile
audit_url "${remote_environment_url}/p/A_Adra%C3%A9n%2C_tanys" desktop
audit_url "${remote_environment_url}/p/A_Alaior%2C_mostren_la_panxa_per_un_guix%C3%B3" desktop
audit_url "${remote_environment_url}/p/Cel_rogent%2C_pluja_o_vent" desktop
audit_url "${remote_environment_url}/p/Tal_far%C3%A0s%2C_tal_trobar%C3%A0s" mobile
audit_url "${remote_environment_url}/obra/Amades_i_Gelats%2C_Joan_%281951%29%3A_Folklore_de_Catalunya._Can%C3%A7oner%2C_3a_ed._1982" desktop
audit_url "${remote_environment_url}/obra/Carol%2C_Roser_%281978-2021%29%3A_Frases_fetes_dels_Països_Catalans" mobile

echo "All audits finished OK :)"
