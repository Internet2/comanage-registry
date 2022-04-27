#!/bin/bash
#
# Build containers for COmanage Registry and associated tools.
#
# Portions licensed to the University Corporation for Advanced Internet
# Development, Inc. ("UCAID") under one or more contributor license agreements.
# See the NOTICE file distributed with this work for additional information
# regarding copyright ownership.
#
# UCAID licenses this file to you under the Apache License, Version 2.0
# (the "License"); you may not use this file except in compliance with the
# License. You may obtain a copy of the License at:
#
# http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

###########################################################################
# Build the Registry base image.
# Globals:
#   None
# Arguments:
#   Base image version, an integer.
#   Build flags, other flags for docker build.
# Outputs:
#   None
###########################################################################
function build_base() {
    local image_version
    local tag
    local version

    image_version="$1"
    if [[ -z "${image_version}" ]]; then
        err "ERROR: image version cannot be empty"
        return 1
    fi

    version="$(version_from_repository)"

    tag="comanage-registry-base:${version}-${image_version}"

    docker build \
        "${@:2}" \
        --tag "${tag}" \
        --build-arg COMANAGE_REGISTRY_VERSION="${version}" \
        --file container/registry/base/Dockerfile \
        .
}

###########################################################################
# Build the Registry basic auth image.
# Globals:
#   None
# Arguments:
#   Base image version, an integer.
#   Image version, an integer.
#   Build flags, other flags for docker build.
# Outputs:
#   None
###########################################################################
function build_basic_auth() {
    local base_image_version
    local image_version
    local tag
    local version

    base_image_version="$1"
    if [[ -z "${base_image_version}" ]]; then
        err "ERROR: base image version cannot be empty"
        return 1
    fi

    image_version="$2"
    if [[ -z "${image_version}" ]]; then
        err "ERROR: image version cannot be empty"
        return 1
    fi

    version="$(version_from_repository)"

    tag="comanage-registry:${version}-basic-auth-${image_version}"

    docker build \
        "${@:3}" \
        --tag "${tag}" \
        --build-arg \
            COMANAGE_REGISTRY_VERSION="${version}" \
        --build-arg \
            COMANAGE_REGISTRY_BASE_IMAGE_VERSION="${base_image_version}" \
        --file container/registry/basic-auth/Dockerfile \
        .
}

###########################################################################
# Build the Registry mod_auth_openidc image.
# Globals:
#   None
# Arguments:
#   Base image version, an integer.
#   Image version, an integer.
#   Build flags, other flags for docker build.
# Outputs:
#   None
###########################################################################
function build_mod_auth_openidc() {
    local base_image_version
    local image_version
    local tag
    local version

    base_image_version="$1"
    if [[ -z "${base_image_version}" ]]; then
        err "ERROR: base image version cannot be empty"
        return 1
    fi

    image_version="$2"
    if [[ -z "${image_version}" ]]; then
        err "ERROR: image version cannot be empty"
        return 1
    fi

    version="$(version_from_repository)"

    tag="comanage-registry:${version}-mod_auth_openidc-${image_version}"

    docker build \
        "${@:3}" \
        --tag "${tag}" \
        --build-arg \
            COMANAGE_REGISTRY_VERSION="${version}" \
        --build-arg \
            COMANAGE_REGISTRY_BASE_IMAGE_VERSION="${base_image_version}" \
        --file container/registry/mod_auth_openidc/Dockerfile \
        .
}

###########################################################################
# Build the Shibboleth SP base image.
# Globals:
#   None
# Arguments:
#   Shibboleth SP version.
#   Image version, an integer.
#   Build flags, other flags for docker build.
# Outputs:
#   None
###########################################################################
function build_shibboleth_sp_base() {
    local image_version
    local tag
    local version

    version="$1"
    if [[ -z "${version}" ]]; then
        err "ERROR: Shibboleth SP version cannot be empty"
        return 1
    fi

    image_version="$2"
    if [[ -z "${image_version}" ]]; then
        err "ERROR: image version cannot be empty"
        return 1
    fi

    tag="comanage-registry-shibboleth-sp-base:${version}-${image_version}"

    docker build \
        "${@:3}" \
        --tag "${tag}" \
        --build-arg \
            SHIBBOLETH_SP_VERSION="${version}" \
        --file container/shibboleth-sp-base/Dockerfile \
        .
}

###########################################################################
# Build the Shibboleth SP with supervisor image.
# Globals:
#   None
# Arguments:
#   Base image version, an integer.
#   Shibboleth SP version.
#   Shibboleth SP base image version, an integer.
#   Image version, an integer.
#   Build flags, other flags for docker build.
# Outputs:
#   None
###########################################################################
function build_shibboleth_sp_supervisor() {
    local base_image_version
    local image_version
    local shibboleth_sp_base_image_version
    local shibboleth_sp_version
    local tag
    local version

    base_image_version="$1"
    if [[ -z "${base_image_version}" ]]; then
        err "ERROR: base image version cannot be empty"
        return 1
    fi

    shibboleth_sp_version="$2"
    if [[ -z "${shibboleth_sp_version}" ]]; then
        err "ERROR: Shibboleth SP version cannot be empty"
        return 1
    fi

    shibboleth_sp_base_image_version="$3"
    if [[ -z "${shibboleth_sp_base_image_version}" ]]; then
        err "ERROR: Shibboleth SP base image version cannot be empty"
        return 1
    fi

    image_version="$4"
    if [[ -z "${image_version}" ]]; then
        err "ERROR: image version cannot be empty"
        return 1
    fi

    version="$(version_from_repository)"

    tag="comanage-registry:${version}-shibboleth-sp-supervisor-${image_version}"

    docker build \
        "${@:5}" \
        --tag "${tag}" \
        --build-arg \
            COMANAGE_REGISTRY_VERSION="${version}" \
        --build-arg \
            COMANAGE_REGISTRY_BASE_IMAGE_VERSION="${base_image_version}" \
        --build-arg \
            COMANAGE_REGISTRY_SHIBBOLETH_SP_VERSION="${shibboleth_sp_version}" \
        --build-arg \
            COMANAGE_REGISTRY_SHIBBOLETH_SP_BASE_IMAGE_VERSION="${shibboleth_sp_base_image_version}" \
        --file container/registry/shibboleth-sp-supervisor/Dockerfile \
        .

}

###########################################################################
# Echo errors to stderr with timestamp.
# Globals:
#   None
# Arguments:
#   None
# Outputs:
#   Writes errors to stderr.
###########################################################################
function err() {
    echo "[$(date +'%Y-%m-%dT%H:%M:%S%z')]: $*" >&2
}

###########################################################################
# Echo usage message to stdout.
# Globals:
#   None
# Arguments:
#   Array of all input parameters
# Outputs:
#   Writes usage message to stdout.
###########################################################################
function usage() {
    local usage

    read -d '' usage <<EOF
$0 [-h] [-t | --tag_suffix ] image_type

where:
    -h, --help          show this help text
    -t, --tag_suffix    suffix for image tag

    image_type is one of
        basic_auth
        shibboleth
        mod_auth_openidc
        all
EOF

    echo "${usage}"
}

###########################################################################
# Use git to inspect repository state and return version string.
# Globals:
#   None
# Arguments:
#   None
# Outputs:
#   Writes version string to stdout.
###########################################################################
function version_from_repository() {
    local branch
    local version

    git symbolic-ref -q HEAD > /dev/null 2>&1
    if (( $? == 0 )); then
        branch="$(git rev-parse --abbrev-ref HEAD)"
        if [[ "${branch}" == "main" ]]; then
            version="$(git describe --tags --abbrev=0)"
        else
            version="${branch}-$(git rev-parse --short HEAD)"
        fi
    else
        version="$(git rev-parse --short HEAD)"
    fi

    echo "${version}"
}

###########################################################################
# Parse command line and execute as specified.
# Globals:
#   SHIBBOLETH_SP_VERSION, string
# Arguments:
#   Array of all input parameters
# Outputs:
#   None
###########################################################################
function main() {
    local build_target
    local gnu_getopt_out
    local tag_suffix

    gnu_getopt_out=$(/usr/bin/getopt \
                     --options ht: \
                     --longoptions help,tag_suffix: \
                     --name 'build.sh' -- "${@:1:3}")

    if [[ $? != 0 ]]; then
        err "ERROR: unable to parse command line"
        exit 1
    fi

    eval set -- "${gnu_getopt_out} ${@:4}"

    while true; do
        case "$1" in
            -h | --help ) usage $@; exit ;;
            -t | --tag_suffix ) tag_suffix="$2"; shift 2 ;;
            -- ) shift; break ;;
            * ) break ;;
        esac
    done

    build_target="$1"

    case "${build_target}" in
        all )
            build_base 1 "${@:2}"
            build_basic_auth 1 "${tag_suffix}" "${@:2}"
            build_shibboleth_sp_base "${SHIBBOLETH_SP_VERSION}" 1 "${@:2}"
            build_shibboleth_sp_supervisor 1 "${SHIBBOLETH_SP_VERSION}" 1 "${tag_suffix}" "${@:2}"
            build_mod_auth_openidc 1 "${tag_suffix}" "${@:2}"
            ;;
        basic_auth )
            build_base 1 "${@:2}"
            build_basic_auth 1 "${tag_suffix}" "${@:2}"
            ;;
        mod_auth_openidc )
            build_base 1 "${@:2}"
            build_mod_auth_openidc 1 "${tag_suffix}" "${@:2}"
            ;;
        shibboleth )
            build_base 1 "${@:2}"
            build_shibboleth_sp_base "${SHIBBOLETH_SP_VERSION}" 1 "${@:2}"
            build_shibboleth_sp_supervisor 1 "${SHIBBOLETH_SP_VERSION}" 1 "${tag_suffix}" "${@:2}"
            ;;
        *)
            err "ERROR: Unrecognized image type"
            echo
            usage
            ;;
    esac
}

# Globals
SHIBBOLETH_SP_VERSION=3.3.0

main "$@"
