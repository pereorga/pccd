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

##############################################################################
# Returns the npx command to run lighthouse.
# Arguments:
#   URL         The URL to validate
#   CATEGORIES  The categories to validate
#   [DEVICE]    (optional) The browser mode ("mobile" or "desktop")
# Returns:
#   Writes the npx command to stdout.
##############################################################################
lighthouse_command() {
    local -r URL=$1
    local -r CATEGORIES=$2
    local -r DEVICE=$3
    local command="npx lighthouse \"${URL}\" --quiet --no-enable-error-reporting --only-categories=\"${CATEGORIES}\" --chrome-flags=\"--headless\" --output=json"

    if [[ ${DEVICE} == "mobile" ]]; then
        command+=" --screenEmulation.mobile --screenEmulation.width=360 --screenEmulation.height=640 --screenEmulation.deviceScaleFactor=2"
    fi

    echo "${command}"
}

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
    local npx_command
    npx_command="$(lighthouse_command "${URL}" "${CATEGORIES}" "${DEVICE}")"

    echo ""
    eval "${npx_command}" > "${JSON_FILENAME}"

    for category in ${CATEGORIES//,/ }; do
        if [[ ${category} == *-* ]]; then
            # Escape hyphens.
            category="[\"${category}\"]"
        fi
        local score
        score=$(jq --raw-output ".categories | .${category} | .score" < "${JSON_FILENAME}")
        if [[ ${score} != 1 ]]; then
            echo "ERROR: ${category} score is ${score} for ${URL} (${DEVICE})." >&2
            npx_command="${npx_command//--chrome-flags=\"--headless\" --output=json/--view}"
            echo "Consider running '${npx_command}'" >&2
            if [[ ${category} != "performance" ]]; then
                exit 255
            fi
        fi
    done

    echo "All essential audits score 100% for ${URL} on ${DEVICE}."
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

readonly URLS=(
    "/"
    "/p/A_Agramunt_comerciants_i_a_T%C3%A0rrega_comediants"
    "/p/A_Adra%C3%A9n%2C_tanys"
    "/p/A_Alaior%2C_mostren_la_panxa_per_un_guix%C3%B3"
    "/p/Cel_rogent%2C_pluja_o_vent"
    "/p/Tal_far%C3%A0s%2C_tal_trobar%C3%A0s"
    "/obra/Amades_i_Gelats%2C_Joan_%281951%29%3A_Folklore_de_Catalunya._Can%C3%A7oner%2C_3a_ed._1982"
    "/obra/Carol%2C_Roser_%281978-2021%29%3A_Frases_fetes_dels_Pa%C3%AFsos_Catalans"
)

readonly DEVICES=("desktop" "mobile")

for url in "${URLS[@]}"; do
    for device in "${DEVICES[@]}"; do
        audit_url "${BASE_URL}${url}" "${device}"
    done
done

echo "All audits finished OK :)"
