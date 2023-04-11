#!/usr/bin/env bash
#
# Audits a running website with Google Lighthouse.
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
    echo "Usage: $(basename "$0")"
    echo "Run multiple tests and checks in the website."
}

if [[ -n $1 ]]; then
    usage
    exit 1
fi

# If the BASE_URL variable is not passed, load it from env file.
if [[ -z ${BASE_URL} ]]; then
    export "$(grep 'BASE_URL=' ../.env | xargs)"
    if [[ -z ${BASE_URL} ]]; then
        echo "ERROR: BASE_URL variable is not set." >&2
        exit 255
    fi
fi

readonly BASE_URL

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

audit_url "${BASE_URL}/" desktop
audit_url "${BASE_URL}/" mobile
audit_url "${BASE_URL}/p/A_Agramunt_comerciants_i_a_T%C3%A0rrega_comediants" desktop
audit_url "${BASE_URL}/p/A_Agramunt_comerciants_i_a_T%C3%A0rrega_comediants" mobile
audit_url "${BASE_URL}/p/A_Adra%C3%A9n%2C_tanys" desktop
audit_url "${BASE_URL}/p/A_Alaior%2C_mostren_la_panxa_per_un_guix%C3%B3" desktop
audit_url "${BASE_URL}/p/Cel_rogent%2C_pluja_o_vent" desktop
audit_url "${BASE_URL}/p/Tal_far%C3%A0s%2C_tal_trobar%C3%A0s" mobile
audit_url "${BASE_URL}/obra/Amades_i_Gelats%2C_Joan_%281951%29%3A_Folklore_de_Catalunya._Can%C3%A7oner%2C_3a_ed._1982" desktop
audit_url "${BASE_URL}/obra/Carol%2C_Roser_%281978-2021%29%3A_Frases_fetes_dels_Països_Catalans" mobile

echo "All audits finished OK :)"
