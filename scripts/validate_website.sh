#!/usr/bin/env bash
#
# Runs some tests and checks against a running website.
#
# URL of the environment can be passed as argument:
#   ./validate_website.sh http://localhost:8091
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
    echo "Usage: $(basename "$0") [ENVIRONMENT_URL] [--fast]"
    echo "Run multiple tests and checks in the website."
    echo ""
    echo "  ENVIRONMENT_URL     The website URL, without trailing slash (default: http://localhost:8092)"
    echo "  --fast              Use it to skip slower validations (webhint, html-validate, linkinator)"
}

if [[ -n $3 ]]; then
    usage
    exit 1
fi

remote_environment_url="http://localhost:8092"
options=""
if [[ $2 == "--fast" ]]; then
    options="--fast"
    remote_environment_url="$1"
elif [[ $1 == "--fast" ]]; then
    options="--fast"
    if [[ -n $2 ]]; then
        remote_environment_url="$2"
    fi
elif [[ -n $1 ]]; then
    remote_environment_url="$1"
fi

readonly remote_environment_url
readonly options

##############################################################################
# Validates URL using curl, HTML Tidy, htmlhint, webhint, linkinator and html-validate.
# Arguments:
#   URL         The URL to validate
#   [--fast]    (optional) Use it to skip webhint, linkinator and html-validate slower validations
##############################################################################
validate_url() {
    local -r URL=$1
    local -r SPEED_MODE=$2
    local -r OUTPUT_FILENAME=../tmp/page.html

    echo ""
    if [[ -z ${SPEED_MODE} ]]; then
        echo "Validating ${URL}"
    else
        echo "Validating ${URL} with ${SPEED_MODE} option"
    fi

    # curl is used to generated the HTML file, for the tools that only support local files. Additionally, HTTP status
    # code is also checked.
    echo "=============="
    echo "curl"
    echo "=============="
    set +e
    local status_code
    status_code=$(curl --compressed -o "${OUTPUT_FILENAME}" --silent --write-out "%{http_code}" "${URL}")
    if [[ ${status_code} != "200" ]]; then
        echo "ERROR: Status code HTTP ${status_code}." >&2
        exit 255
    else
        echo "No HTTP errors."
    fi

    # HTACG HTML Tidy is an old tool written in C that checks code against modern standards. See .tidyrc file.
    echo "=============="
    echo "HTML Tidy"
    echo "=============="
    local error
    error=$(tidy -config ../.tidyrc "${OUTPUT_FILENAME}" 2>&1 > /dev/null)
    if [[ -n ${error} ]]; then
        echo "ERROR reported in HTML Tidy: ${error}" >&2
        exit 255
    else
        echo "No errors."
    fi
    set -e -o pipefail

    # Check that $ signs are not found in the HTML, if for some reason a PHP variable is not printed properly.
    if grep -q -r -F -m 1 '$' "${OUTPUT_FILENAME}"; then
        echo 'ERROR: Dollar character ($) found.' >&2
        exit 255
    fi

    # html-validator was previously used (via html-validator-cli)
    # html-validate is used instead now, which is local and has additional checks to the ones provided by
    # html-validator. See: https://github.com/zrrrzzt/html-validator/issues/162
    #echo "=============="
    #echo "html-validator"
    #echo "=============="
    #npx html-validator --verbose --file="${OUTPUT_FILENAME}"

    # htmlhint is a quick static code analysis tool for HTML. See .htmlhintrc file.
    echo "=============="
    echo "htmlhint"
    echo "=============="
    npx htmlhint "${OUTPUT_FILENAME}"

    if [[ ${SPEED_MODE} != "--fast" ]]; then
        # webhint is one of the most extensive testing suites. It uses Chromium or Edge under the hood. Unfortunately it
        # is slow, and probably a bit unmaintained. See .hintrc settings file.
        echo "=============="
        echo "webhint"
        echo "=============="
        npx hint --formatters html --output ../tmp/ --config ../.hintrc "${URL}"

        # linkinator works well for checking that all local links work properly.
        echo "=============="
        echo "linkinator"
        echo "=============="
        npx linkinator "${URL}" --verbosity error --skip "^(?!${remote_environment_url})"

        # html-validate is an offline HTML5 validator with strict parsing. Apart from parsing and content model
        # validation it also includes style, cosmetics, good practice and accessibility rules. See .htmlvalidate.json.
        echo "=============="
        echo "html-validate"
        echo "=============="
        curl --fail --silent "${URL}" | npx html-validate --stdin --config=../.htmlvalidate.json
        echo "No html-validate issues."
    fi
}

##############################################################################
# Validates an HTTP 404 page.
# Arguments:
#   URL         The URL to validate
##############################################################################
validate_url_404() {
    local -r URL=$1
    local -r OUTPUT_FILENAME=../tmp/page.html

    echo ""
    echo "Validating 404 page ${URL}..."

    echo "=============="
    echo "curl"
    echo "=============="
    set +e
    local status_code
    status_code=$(curl --compressed -o "${OUTPUT_FILENAME}" --silent --write-out "%{http_code}" "${URL}")
    if [[ ${status_code} != "404" ]]; then
        echo "Error: Status code HTTP ${status_code}." >&2
        exit 255
    else
        echo "No HTTP errors."
    fi

    echo "=============="
    echo "HTML Tidy"
    echo "=============="
    local error
    error=$(tidy -config ../.tidyrc "${OUTPUT_FILENAME}" 2>&1 > /dev/null)
    if [[ -n ${error} ]]; then
        echo "ERROR reported in HTML Tidy: ${error}" >&2
        exit 255
    else
        echo "No errors."
    fi
    set -e -o pipefail
}

# Check 404 pages.
validate_url_404 "${remote_environment_url}/p/A_Abrerasefserewrwe"
validate_url_404 "${remote_environment_url}/asdfasdfsadfs"

# Validate HTML.
validate_url "${remote_environment_url}/" "${options}"
validate_url "${remote_environment_url}/projecte" "${options}"
validate_url "${remote_environment_url}/top100" "${options}"
validate_url "${remote_environment_url}/llibres" "${options}"
validate_url "${remote_environment_url}/instruccions" "${options}"
validate_url "${remote_environment_url}/credits" "${options}"
validate_url "${remote_environment_url}/p/A_Abrera%2C_donen_garses_per_perdius" "${options}"
validate_url "${remote_environment_url}/p/Qui_no_vulgui_pols%2C_que_no_vagi_a_l%27era" "${options}"
validate_url "${remote_environment_url}/obra/Pons_Lluch%2C_Josep_%281993%29%3A_Refranyer_menorqu%C3%AD" "${options}"
validate_url "${remote_environment_url}/?pagina=5147" "${options}"
validate_url "${remote_environment_url}/?mode=&cerca=ca%C3%A7a&variant=&mostra=10" "${options}"
validate_url "${remote_environment_url}/p/A_Adra%C3%A9n%2C_tanys" "${options}"
validate_url "${remote_environment_url}/p/A_Alaior%2C_mostren_la_panxa_per_un_guix%C3%B3" "${options}"
# This page is always executed with --fast, otherwise it is extremely slow.
validate_url "${remote_environment_url}/?mostra=infinit" --fast

echo "All validation tests finished OK :)"
