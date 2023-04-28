#!/usr/bin/env bash
#
# Updates composer/phive/phar/yarn/pecl/apt/brew/maven dependencies and Docker images.
#
# This script is called by `yarn run update` script.
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
    echo "Usage: $(basename "$0") COMMAND"
    echo "Update project dev dependencies."
    echo ""
    echo "    help"
    echo "      Shows this help and exits"
    echo "    os-packages"
    echo "      Updates brew/apt packages"
    echo "    yarn"
    echo "      Updates all yarn dev direct packages to latest release"
    echo "    composer"
    echo "      Updates all composer dependencies to latest release, including non-direct dependencies"
    echo "    phive"
    echo "      Updates all phive (phar) packages and phive itself to latest releases"
    echo "    apc-gui"
    echo "      Updates apc.php file to latest revision"
    echo "    opcache-gui"
    echo "      Updates OPcache GUI to latest revision"
    echo "    docker"
    echo "      Updates Docker images in Docker files and docker-compose.yml to next release"
    echo "    pecl"
    echo "      Updates pecl packages in Docker files to latest version"
    echo "    mvn"
    echo "      Checks for newer versions of Maven dependencies (for CV export script)"
}

##############################################################################
# Updates a pecl install command inside a Docker file with the latest release of a pecl package.
# Arguments:
#   A Docker file, a path (e.g. ".docker/Dockerfile")
#   A pecl package (e.g. "apcu")
##############################################################################
update_pecl_package_dockerfile() {
    local -r DOCKER_FILE="$1"
    local -r PECL_PACKAGE="$2"
    local latest_version

    latest_version=$(pecl remote-info "${PECL_PACKAGE}" | grep -F Latest | cut -c13-)
    if [[ -z ${latest_version} ]]; then
        echo "Warning: Could not find latest version of ${PECL_PACKAGE} using pecl, trying it with curl..."
        # Try to check https://pecl.php.net/rest/r/PECL_PACKAGE/allreleases.xml manually.
        # We use sed to not add a new dependency (xmlstarlet/yq/xq/dasel), and because grep -P does not work in POSIX/macOS.
        latest_version=$(curl --silent --fail "https://pecl.php.net/rest/r/${PECL_PACKAGE}/allreleases.xml" |
            grep -F '<s>stable</s>' |
            sed -n 's:.*<v>\(.*\)</v>.*:\1:p' |
            sort --version-sort |
            tail -n1)
    fi
    sed -i'.original' -e "s/pecl install ${PECL_PACKAGE}-[^ ]*/pecl install ${PECL_PACKAGE}-${latest_version}/" "${DOCKER_FILE}"
    rm "${DOCKER_FILE}.original"
}

##############################################################################
# Checks whether a specific version of a hub.docker.com image exists.
# Arguments:
#   An image name (e.g. "php")
#   An versioned image tag (e.g. "8.2.0-apache-buster")
# Returns:
#   0 if the release exists, 1 otherwise.
##############################################################################
version_exists_dockerhub() {
    local -r IMAGE_NAME="$1"
    local -r IMAGE_TAG="$2"
    local count
    count=$(curl --silent --fail "https://hub.docker.com/v2/repositories/library/${IMAGE_NAME}/tags/?page_size=25&page=1&name=${IMAGE_TAG}" |
        jq --raw-output '.count')
    if [[ ${count} == 0 ]]; then
        return 1
    else
        return 0
    fi
}
export -f version_exists_dockerhub

##############################################################################
# Increments a version.
# Arguments:
#   A versioned image tag or version (e.g. "8.2.0-apache-buster" or "8.2.0")
# Returns:
#   Writes the incremented version to stdout (e.g. "8.2.1-apache-buster" or "8.2.1").
##############################################################################
increment_version() {
    local -r VERSION="$1"
    local current_min_version prefix next_min_version next_version

    current_min_version=$(echo "${VERSION}" | grep -E -o '[0-9]+$')
    prefix=$(echo "${VERSION}" | sed -e "s/${current_min_version}$//")
    next_min_version=$((current_min_version + 1))
    next_version="${prefix}${next_min_version}"

    echo "${next_version}"
}
export -f increment_version

##############################################################################
# Checks for a newer version of a hub.docker.com image specified in a Docker file, and updates it.
# Arguments:
#   A Docker file, a path (e.g. ".docker/Dockerfile")
#   An image name (e.g. "php")
##############################################################################
check_version_docker_file() {
    local -r DOCKER_FILE="$1"
    local -r IMAGE_NAME="$2"
    local current_version current_image next_version next_image

    current_version=$(grep -F "${IMAGE_NAME}:" "${DOCKER_FILE}" | grep -E -o '[0-9\.]+')
    current_image=$(grep -F "${IMAGE_NAME}:" "${DOCKER_FILE}" | sed -e "s/FROM ${IMAGE_NAME}://")
    next_version=$(increment_version "${current_version}")
    next_image=$(echo "${current_image}" | sed -e "s/${current_version}/${next_version}/")
    if version_exists_dockerhub "${IMAGE_NAME}" "${next_image}"; then
        echo "${IMAGE_NAME} Docker image is out of date, updating to ${next_image}..."
        sed -i'.original' -e "s/${current_image}/${next_image}/" "${DOCKER_FILE}"
        rm "${DOCKER_FILE}.original"
        exit 1
    else
        echo "OK: ${IMAGE_NAME} Docker image is up to date."
    fi
}

##############################################################################
# Checks for a newer version of a hub.docker.com image specified in a Docker Compose file, and updates it.
# Arguments:
#   A Docker Compose file, a path (e.g. "docker-compose.yml")
#   An image name (e.g. "mariadb")
##############################################################################
check_version_docker_compose() {
    local -r COMPOSE_FILE="$1"
    local -r IMAGE_NAME="$2"
    local current_version next_version

    current_version=$(grep -F 'image:' "${COMPOSE_FILE}" | grep -F "${IMAGE_NAME}:" | grep -E -o '[0-9\.]+')
    next_version=$(increment_version "${current_version}")
    if version_exists_dockerhub "${IMAGE_NAME}" "${next_version}"; then
        echo "${IMAGE_NAME} Docker image is out of date, updating to ${next_version}..."
        sed -i'.original' -e "s/${current_version}/${next_version}/" "${COMPOSE_FILE}"
        rm "${COMPOSE_FILE}.original"
        exit 1
    else
        echo "OK: ${IMAGE_NAME} Docker image is up to date."
    fi
}

##############################################################################
# Updates Homebrew packages and apt-get packages, if commands are available.
# Arguments:
#   None
##############################################################################
update_os_packages() {
    if [[ -x "$(command -v apt-get)" ]]; then
        # We consider this to be Debian-based.
        echo "Installing/updating apt-get packages..."
        sudo apt-get update -y && xargs -a apt-packages.txt sudo apt-get install -y
        if [[ -x "$(command -v brew)" ]]; then
            echo "Installing/updating brew packages for systems that have apt-get..."
            brew bundle install --file=ubuntu.Brewfile
        else
            echo "Note: brew command is not available"
        fi
    else
        # This is likely macOS, a non-Debian Linux distribution (untested) or another POSIX system (untested).
        if [[ -x "$(command -v brew)" ]]; then
            echo "Installing/updating brew packages..."
            brew bundle install
        else
            echo "Note: brew command is not available"
        fi
    fi
}

##############################################################################
# Installs newest major versions of composer packages. See https://stackoverflow.com/a/74760024/1391963
# Arguments:
#   None
##############################################################################
update_composer_major() {
    # Update non-dev dependencies.
    tools/composer show --no-dev --direct --name-only |
        xargs tools/composer require

    # Update dev dependencies.
    grep -F -v -f \
        <(tools/composer show --direct --no-dev --name-only | sort) \
        <(tools/composer show --direct --name-only | sort) |
        xargs tools/composer require --dev
}

##############################################################################
# Installs newest major versions of yarn dev packages. See https://stackoverflow.com/a/75525951/1391963
# Arguments:
#   None
##############################################################################
update_yarn_major() {
    jq '.devDependencies | keys | .[]' package.json | xargs yarn add --dev --silent
}

##############################################################################
# Checks for newer versions of dependencies in a pom.xml file (Maven).
# Arguments:
#   A pom.xml file, a path (e.g. "pom.xml")
##############################################################################
check_dependencies_mvn() {
    local -r POM_FILE="$1"
    local pom_path
    pom_path="$(dirname "${POM_FILE}")"

    if [[ -f ${POM_FILE} ]]; then
        echo "Checking dependencies in ${POM_FILE}..."
        (cd "${pom_path}" && mvn versions:display-dependency-updates > versions.txt)
        if grep -q -F 'The following dependencies in Dependencies have newer versions' "${pom_path}/versions.txt"; then
            echo "Error: There are newer versions of dependencies specified in ${pom_path}:"
            cat "${pom_path}/versions.txt"
            exit 1
        else
            echo "OK: All mvn dependencies are using latest versions."
        fi
    else
        echo "Warning: ${POM_FILE} does not exist."
    fi
}

if [[ $# != 1 ]]; then
    usage
    exit 1
fi

if [[ $1 == "help" ]]; then
    usage
    exit 0
fi

if [[ $1 == "os-packages" ]]; then
    update_os_packages
    exit 0
fi

if [[ $1 == "pecl" ]]; then
    update_pecl_package_dockerfile .docker/Dockerfile apcu
    update_pecl_package_dockerfile .docker/Dockerfile xhprof
    exit 0
fi

if [[ $1 == "composer" ]]; then
    update_composer_major
    tools/composer update --with-all-dependencies
    exit 0
fi

if [[ $1 == "phive" ]]; then
    tools/phive selfupdate --trust-gpg-keys
    yes | tools/phive update --force-accept-unsigned
    exit 0
fi

if [[ $1 == "apc-gui" ]]; then
    curl --silent --fail https://raw.githubusercontent.com/krakjoe/apcu/master/apc.php > src/third_party/apc.php
    exit 0
fi

if [[ $1 == "opcache-gui" ]]; then
    curl --silent --fail https://raw.githubusercontent.com/amnuts/opcache-gui/master/index.php > src/third_party/opcache-gui.php
    exit 0
fi

if [[ $1 == "yarn" ]]; then
    update_yarn_major
    exit 0
fi

if [[ $1 == "docker" ]]; then
    check_version_docker_file .docker/Dockerfile php
    check_version_docker_compose docker-compose.yml mariadb
    check_version_docker_compose docker-compose.yml varnish
    exit 0
fi

if [[ $1 == "mvn" ]]; then
    check_dependencies_mvn scripts/common-voice-export/third_party/pccd-lt-filter/pom.xml
    exit 0
fi

usage
exit 1
