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
#   Full image name prefix, a string.
#   Tag label, a string.
#   Tag suffix, a string.
#   Docker build flags, other flags for docker build.
# Outputs:
#   None
###########################################################################
function build_base() {
    local docker_build_command
    local docker_build_flags
    local label
    local prefix
    local suffix

    prefix="$1"
    label="$2"
    suffix="$3"

    if [[ -z "${label}" ]]; then
        err "ERROR:build_base: label cannot be empty"
        return 1
    fi

    if [[ -z "${suffix}" ]]; then
        err "ERROR:build_base: suffix cannot be empty"
        return 1
    fi

    declare -a docker_build_flags=("${@:4}")

    tag="comanage-registry-base:${label}-${suffix}"

    docker_build_command=(docker build)

    if ((${#docker_build_flags[@]})); then
        for flag in "${docker_build_flags[@]}"; do
            docker_build_command+=("${flag}")
        done
    fi

    docker_build_command+=(--tag "${tag}")
    docker_build_command+=(--build-arg COMANAGE_REGISTRY_VERSION="${label}")
    docker_build_command+=(--file container/registry/base/Dockerfile)
    docker_build_command+=(.)

    "${docker_build_command[@]}"

    if [[ -n "${prefix}" ]]; then
        target="${prefix}${tag}"
        docker tag "${tag}" "${target}"
    fi
}

###########################################################################
# Build the Registry basic auth image.
# Globals:
#   None
# Arguments:
#   Full image name prefix, a string.
#   Tag label, a string.
#   Tag suffix, a string.
#   Docker build flags, other flags for docker build.
# Outputs:
#   None
###########################################################################
function build_basic_auth() {
    local docker_build_command
    local docker_build_flags
    local label
    local prefix
    local suffix

    prefix="$1"
    label="$2"
    suffix="$3"

    if [[ -z "${label}" ]]; then
        err "ERROR:build_basic_auth: label cannot be empty"
        return 1
    fi

    if [[ -z "${suffix}" ]]; then
        err "ERROR:build_basic_auth: suffix cannot be empty"
        return 1
    fi

    declare -a docker_build_flags=("${@:4}")

    tag="comanage-registry:${label}-basic-auth-${suffix}"

    docker_build_command=(docker build)

    if ((${#docker_build_flags[@]})); then
        for flag in "${docker_build_flags[@]}"; do
            docker_build_command+=("${flag}")
        done
    fi

    docker_build_command+=(--tag "${tag}")
    docker_build_command+=(--build-arg COMANAGE_REGISTRY_VERSION="${label}")
    docker_build_command+=(--build-arg COMANAGE_REGISTRY_BASE_IMAGE_VERSION="${suffix}")
    docker_build_command+=(--file container/registry/basic-auth/Dockerfile)
    docker_build_command+=(.)

    "${docker_build_command[@]}"

    if [[ -n "${prefix}" ]]; then
        target="${prefix}${tag}"
        docker tag "${tag}" "${target}"
    fi
}

###########################################################################
# Build the Registry mod_auth_openidc image.
# Globals:
#   None
# Arguments:
#   Full image name prefix, a string.
#   Tag label, a string.
#   Tag suffix, a string.
#   Docker build flags, other flags for docker build.
# Outputs:
#   None
###########################################################################
function build_mod_auth_openidc() {
    local docker_build_command
    local docker_build_flags
    local label
    local prefix
    local suffix

    prefix="$1"
    label="$2"
    suffix="$3"

    if [[ -z "${label}" ]]; then
        err "ERROR:build_mod_auth_openidc: label cannot be empty"
        return 1
    fi

    if [[ -z "${suffix}" ]]; then
        err "ERROR:build_mod_auth_openidc: suffix cannot be empty"
        return 1
    fi

    declare -a docker_build_flags=("${@:4}")

    tag="comanage-registry:${label}-mod_auth_openidc-${suffix}"

    docker_build_command=(docker build)

    if ((${#docker_build_flags[@]})); then
        for flag in "${docker_build_flags[@]}"; do
            docker_build_command+=("${flag}")
        done
    fi

    docker_build_command+=(--tag "${tag}")
    docker_build_command+=(--build-arg COMANAGE_REGISTRY_VERSION="${label}")
    docker_build_command+=(--build-arg COMANAGE_REGISTRY_BASE_IMAGE_VERSION="${suffix}")
    docker_build_command+=(--file container/registry/mod_auth_openidc/Dockerfile)
    docker_build_command+=(.)

    "${docker_build_command[@]}"

    if [[ -n "${prefix}" ]]; then
        target="${prefix}${tag}"
        docker tag "${tag}" "${target}"
    fi
}

###########################################################################
# Build the Shibboleth SP base image.
# Globals:
#   None
# Arguments:
#   Full image name prefix, a string.
#   Shibboleth SP version.
#   Tag suffix, a string.
#   Docker build flags, other flags for docker build.
# Outputs:
#   None
###########################################################################
function build_shibboleth_sp_base() {
    local docker_build_command
    local docker_build_flags
    local label
    local prefix
    local suffix

    prefix="$1"
    label="$2"
    suffix="$3"

    if [[ -z "${label}" ]]; then
        err "ERROR:build_shibboleth_sp_base: label cannot be empty"
        return 1
    fi

    if [[ -z "${suffix}" ]]; then
        err "ERROR:build_shibboleth_sp_base: suffix cannot be empty"
        return 1
    fi

    declare -a docker_build_flags=("${@:4}")

    tag="comanage-registry-shibboleth-sp-base:${label}-${suffix}"

    docker_build_command=(docker build)

    if ((${#docker_build_flags[@]})); then
        for flag in "${docker_build_flags[@]}"; do
            docker_build_command+=("${flag}")
        done
    fi

    docker_build_command+=(--tag "${tag}")
    docker_build_command+=(--build-arg SHIBBOLETH_SP_VERSION="${label}")
    docker_build_command+=(--file container/shibboleth-sp-base/Dockerfile)
    docker_build_command+=(.)

    "${docker_build_command[@]}"

    if [[ -n "${prefix}" ]]; then
        target="${prefix}${tag}"
        docker tag "${tag}" "${target}"
    fi
}

###########################################################################
# Build the Shibboleth SP with supervisor image.
# Globals:
#   None
# Arguments:
#   Full image name prefix, a string.
#   Tag label, a string.
#   Tag suffix, a string.
#   Shibboleth SP version.
#   Shibboleth SP base image version.
#   Docker build flags, other flags for docker build.
# Outputs:
#   None
###########################################################################
function build_shibboleth_sp_supervisor() {
    local docker_build_command
    local docker_build_flags
    local label
    local prefix
    local shib_label
    local shib_suffix
    local suffix

    prefix="$1"
    label="$2"
    suffix="$3"
    shib_label="$4"
    shib_suffix="$5"

    if [[ -z "${label}" ]]; then
        err "ERROR:build_shibboleth_sp_supervisor: label cannot be empty"
        return 1
    fi

    if [[ -z "${suffix}" ]]; then
        err "ERROR:build_shibboleth_sp_supervisor: suffix cannot be empty"
        return 1
    fi

    if [[ -z "${shib_label}" ]]; then
        err "ERROR:build_shibboleth_sp_supervisor: shib_label cannot be empty"
        return 1
    fi

    if [[ -z "${shib_suffix}" ]]; then
        err "ERROR:build_shibboleth_sp_supervisor: shib_suffix cannot be empty"
        return 1
    fi

    declare -a docker_build_flags=("${@:6}")

    tag="comanage-registry:${label}-shibboleth-sp-supervisor-${suffix}"

    docker_build_command=(docker build)

    if ((${#docker_build_flags[@]})); then
        for flag in "${docker_build_flags[@]}"; do
            docker_build_command+=("${flag}")
        done
    fi

    docker_build_command+=(--tag "${tag}")
    docker_build_command+=(--build-arg COMANAGE_REGISTRY_VERSION="${label}")
    docker_build_command+=(--build-arg COMANAGE_REGISTRY_BASE_IMAGE_VERSION="${suffix}")
    docker_build_command+=(--build-arg COMANAGE_REGISTRY_SHIBBOLETH_SP_VERSION="${shib_label}")
    docker_build_command+=(--build-arg COMANAGE_REGISTRY_SHIBBOLETH_SP_BASE_IMAGE_VERSION="${shib_suffix}")
    docker_build_command+=(--file container/registry/shibboleth-sp-supervisor/Dockerfile)
    docker_build_command+=(.)

    "${docker_build_command[@]}"

    if [[ -n "${prefix}" ]]; then
        target="${prefix}${tag}"
        docker tag "${tag}" "${target}"
    fi
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
NAME
    $0 - build COmanage Registry container images

SYNOPSIS
    $0 -s|--suffix=SUFFIX [OPTION]... PRODUCT

DESCRIPTION
    Build COmanage Registry container images.

    PRODUCT is one of 
        registry AUTHENTICATION
        cron
        slapd

    where AUTHENTICATION is one of
        basic-auth
        shibboleth-sp-supervisor
        mod_auth_openidc
        all

    The full name of the built images has the format

    IMAGE_REGISTRY/REPOSITORY/NAME:TAG

    When PRODUCT is registry NAME has the format

    comanage-registry

    and TAG has the format

    LABEL-AUTHENTICATION-SUFFIX

    If not specified LABEL is determined by inspecting
    the source tree and has the format

    GITHUB_TAG|GITHUB_BRANCH-COMMIT

    -h, --help
            show this usage message

    --image_registry
            image registry, default is none

    -l, --label
            label to use in image tag, default is determined
            by inspecting the source tree and has the format
            GITHUB_TAG for source tree tags or
            GITHUB_BRANCH-COMMIT when building from a branch

    --no-cache
            passed to docker build if present

    -o,--owner
            synonym for repository

    --repository
            image repository, default is none,
            required if image_registry is specified

    --rm
            passed to docker build if present

    -s, --suffix
            required image tag suffix

EXAMPLES
    $0 -s 1 registry all
        Build all Registry images with tag suffix 1

    $0 --suffix=2022-05-01 registry shibboleth-sp-supervisor
        Build Registry image with Shibboleth SP authentication
        and the tag suffix 2022-05-01. The Python Supervisor
        system is used to start Apache HTTP Server and the
        Shibboleth SP shibd daemon.

    $0 -s 1 --repository=myorg registry basic-auth
        Build Registry image with basic authentication,
        repository myorg, and tag suffix 1. The full name of
        the image will have the format
        myorg/comanage-registry:LABEL-basic-auth-1

    $0 -s 1 --image_registry=server.my.org --repository=myorg
            registry basic-auth
        Build Registry image with basic authentication,
        repository myorg, image registry server.my.org, and tag suffix 1.
        The full name of the image will have the format
        my.server.org/myorg/comanage-registry:LABEL-basic-auth-1

    $0 --suffix=mytag --no-cache registry mod_auth_openidc
        Build Registry image with OIDC authentication and tag suffix
        mytag and pass --no-cache to the docker build command

    $0 -s 20220501 --label mylabel registry shibboleth-sp-supervisor
        Build Registry image with Shibboleth SP authentication, tag
        suffix 20220501, and label mylabel. The full name of the image
        will have the format
        comanage-registry:mylabel-shibboleth-sp-supervisor-20220501
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
function label_from_repository() {
    local branch
    local label

    git symbolic-ref -q HEAD > /dev/null 2>&1
    if (( $? == 0 )); then
        branch="$(git rev-parse --abbrev-ref HEAD)"
        if [[ "${branch}" == "main" ]]; then
            label="$(git describe --tags --abbrev=0)"
        else
            label="${branch}-$(git rev-parse --short HEAD)"
        fi
    else
        label="$(git rev-parse --short HEAD)"
    fi

    echo "${label}"
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
    local authentication
    local docker_build_flags
    local gnu_getopt_out
    local image_registry
    local label=""
    local prefix=""
    local product
    local repository
    local suffix

    declare -a docker_build_flags=()

    gnu_getopt_out=$(/usr/bin/getopt \
                     --options hl:os: \
                     --longoptions help \
                     --longoptions image_registry: \
                     --longoptions label: \
                     --longoptions no-cache \
                     --longoptions owner: \
                     --longoptions repository: \
                     --longoptions rm \
                     --longoptions suffix: \
                     --name 'build.sh' -- "${@}")

    if [[ $? != 0 ]]; then
        err "ERROR: unable to parse command line"
        exit 1
    fi

    eval set -- "${gnu_getopt_out}"

    while true; do
        case "$1" in
            -h | --help ) usage $@; exit ;;
            --image_registry ) image_registry="$2"; shift 2 ;;
            -l | --label ) label="$2"; shift 2 ;;
            --no-cache ) docker_build_flags+=(--no-cache) ; shift 1 ;;
            -o | --owner ) repository="$2"; shift 2 ;;
            --repository ) repository="$2"; shift 2 ;;
            --rm ) docker_build_flags+=(--rm) ; shift 1 ;;
            -s | --suffix ) suffix="$2"; shift 2 ;;
            -- ) shift; break ;;
            * ) break ;;
        esac
    done

    if [[ -z "${suffix}" ]]; then
        err "ERROR: --suffix must be specified"
        exit 1
    fi

    if [[ -z "${repository}" && -n "${image_registry}" ]]; then
        err "ERROR: --repository must be specified if --image_registry is specified"
        exit 1
    fi
    
    if [[ -z "${label}" ]]; then
        label="$(label_from_repository)"
    fi

    if [[ -n "${repository}" ]]; then
        prefix="${repository}/"
        if [[ -n "${image_registry}" ]]; then
            prefix="${image_registry}/${prefix}"
        fi
    fi

    product="$1"

    case "${product}" in
        registry )
            authentication="$2"
            case "${authentication}" in
                all )
                    build_base "${prefix}" "${label}" "${suffix}" "${docker_build_flags[@]}"
                    build_basic_auth "${prefix}" "${label}" "${suffix}" "${docker_build_flags[@]}"
                    build_mod_auth_openidc "${prefix}" "${label}" "${suffix}" "${docker_build_flags[@]}"
                    build_shibboleth_sp_base "${prefix}" "${SHIBBOLETH_SP_VERSION}" "${suffix}" "${docker_build_flags[@]}"
                    build_shibboleth_sp_supervisor "${prefix}" "${label}" "${suffix}" "${SHIBBOLETH_SP_VERSION}" "${suffix}" "${docker_build_flags[@]}"
                    ;;
                basic-auth )
                    build_base "${prefix}" "${label}" "${suffix}" "${docker_build_flags[@]}"
                    build_basic_auth "${prefix}" "${label}" "${suffix}" "${docker_build_flags[@]}"
                    ;;
                mod_auth_openidc )
                    build_base "${prefix}" "${label}" "${suffix}" "${docker_build_flags[@]}"
                    build_mod_auth_openidc "${prefix}" "${label}" "${suffix}" "${docker_build_flags[@]}"
                    ;;
                shibboleth-sp-supervisor )
                    build_base "${prefix}" "${label}" "${suffix}" "${docker_build_flags[@]}"
                    build_shibboleth_sp_base "${prefix}" "${SHIBBOLETH_SP_VERSION}" "${suffix}" "${docker_build_flags[@]}"
                    build_shibboleth_sp_supervisor "${prefix}" "${label}" "${suffix}" "${SHIBBOLETH_SP_VERSION}" "${suffix}" "${docker_build_flags[@]}"
                    ;;
                *)
                    err "ERROR: Unrecognized authentication"
                    echo
                    usage
                    ;;
            esac
            ;;
        slapd )
            ;;
        *)
            err "ERROR: unrecogized product"
            echo
            usage
            ;;
    esac
}

# Globals
SHIBBOLETH_SP_VERSION=3.3.0

main "$@"
