#!/bin/bash

# COmanage Registry Shibboleth SP Dockerfile entrypoint
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

if [ -n "$COMANAGE_DEBUG" ]
then
    OUTPUT=/dev/stdout
else
    OUTPUT=/dev/null
fi

##########################################
# Consume injected environment variables
# Globals:
#   See function
# Arguments:
#   None
# Returns:
#   None
##########################################
function comanage_shibboleth_sp_utils::consume_injected_environment() {

    echo "Examining environment variables for Shibboleth SP..." > "$OUTPUT" 

    # Configuration details that may be injected through environment
    # variables or the contents of files.
    #
    # SHIBBOLETH_SP_METADATA_PROVIDER_XML may also be injected in the
    # same way but because of the presence of special characters in the
    # XML it is handled differently.

    injectable_config_vars=( 
        SHIBBOLETH_SP_ENTITY_ID
        SHIBBOLETH_SP_CERT
        SHIBBOLETH_SP_PRIVKEY
        SHIBBOLETH_SP_SIGNING_CERT
        SHIBBOLETH_SP_SIGNING_PRIVKEY
        SHIBBOLETH_SP_ENCRYPT_CERT
        SHIBBOLETH_SP_ENCRYPT_PRIVKEY
        SHIBBOLETH_SP_SAMLDS_URL
    )

    # If the file associated with a configuration variable is present then 
    # read the value from it into the appropriate variable. So for example
    # if the variable COMANAGE_REGISTRY_DATASOURCE_FILE exists and its
    # value points to a file on the file system then read the contents
    # of that file into the variable COMANAGE_REGISTRY_DATASOURCE.

    for config_var in "${injectable_config_vars[@]}"
    do
        eval file_name=\$"${config_var}_FILE";

        if [ -e "$file_name" ]; then
            payload=`cat $file_name`
            declare "${config_var}"="${payload}"
        fi
    done

    echo "Done examining environment variables" > "$OUTPUT"
}

##########################################
# Prepare shibboleth2.xml configuration file
# Globals:
#   OUTPUT
#   SHIBBOLETH_SP_ENTITY_ID
#   SHIBBOLETH_SP_SAMLDS_URL
#   SHIBBOLETH_SP_METADATA_PROVIDER_XML_FILE
# Arguments:
#   None
# Returns:
#   None
##########################################
function comanage_shibboleth_sp_utils::prepare_shibboleth2xml() {
    
    local shib_file
    local xml_content_file
    local sed_script_file

    # If no shibboleth2.xml file is present then create one using 
    # injected information or defaults that are not particularly
    # useful in a federated context but will allow shibd to start.
    shib_file='/etc/shibboleth/shibboleth2.xml'

    if [[ ! -e "${shib_file}" ]]; then
        cp "${shib_file}.template" "${shib_file}" > "${OUTPUT}" 2>&1
        sed -i -e s@%%SHIBBOLETH_SP_ENTITY_ID%%@"${SHIBBOLETH_SP_ENTITY_ID:-https://comanage.registry/shibboleth}"@ "${shib_file}" > "${OUTPUT}" 2>&1
        sed -i -e s@%%SHIBBOLETH_SP_SAMLDS_URL%%@"${SHIBBOLETH_SP_SAMLDS_URL:-https://localhost/registry/pages/eds/index}"@ "${shib_file}" > "${OUTPUT}" 2>&1

        # The metadata provider injected input most likely contains special characters
        # so use a sed script instead of simple substitution on the command line.

        if [[ -n "${SHIBBOLETH_SP_METADATA_PROVIDER_XML_FILE}" ]]; then
            xml_content_file="${SHIBBOLETH_SP_METADATA_PROVIDER_XML_FILE}"
        else
            xml_content_file=`/bin/mktemp`
            echo "${SHIBBOLETH_SP_METADATA_PROVIDER_XML:-}" > "${xml_content_file}"
        fi

        sed_script_file=`/bin/mktemp`
        cat > ${sed_script_file}<<EOF
/%%SHIBBOLETH_SP_METADATA_PROVIDER_XML%%/ {
    r ${xml_content_file}
    d
}
EOF

        sed -i -f "${sed_script_file}" "${shib_file}" > "${OUTPUT}" 2>&1
        
        chmod 0644 "${shib_file}" > "${OUTPUT}" 2>&1

        rm -f "${xml_content_file}" > "${OUTPUT}" 2>&1
        rm -f "${sed_script_file}" > "${OUTPUT}" 2>&1

    fi
}

##########################################
# Prepare SAML certs and keys
# Globals:
#   SHIBBOLETH_SP_CERT
#   SHIBBOLETH_SP_PRIVKEY
#   SHIBBOLETH_SP_SIGNING_CERT
#   SHIBBOLETH_SP_SIGNING_PRIVKEY
#   SHIBBOLETH_SP_ENCRYPT_CERT
#   SHIBBOLETH_SP_ENCRYPT_PRIVKEY
# Arguments:
#   None
# Returns:
#   None
##########################################
function comanage_shibboleth_sp_utils::prepare_saml_cert_key() {

    local saml_file
    local owner

    if [[ -e '/etc/debian_version' ]]; then
        owner='_shibd'
    elif [[ -e '/etc/centos-release' ]]; then
        owner='shibd'
    fi

    # If defined use configured location of Shibboleth SP SAML certificate and key.
    saml_file='/etc/shibboleth/sp-cert.pem'
    if [[ -n "${SHIBBOLETH_SP_CERT}" ]]; then
        cp "${SHIBBOLETH_SP_CERT}" "${saml_file}"
        chown "${owner}" "${saml_file}"
        chmod 0644 "${saml_file}"
    fi

    saml_file='/etc/shibboleth/sp-key.pem'
    if [[ -n "${SHIBBOLETH_SP_PRIVKEY}" ]]; then
        cp "${SHIBBOLETH_SP_PRIVKEY}" "${saml_file}"
        chown "${owner}" "${saml_file}"
        chmod 0600 "${saml_file}"
    fi

    saml_file='/etc/shibboleth/sp-signing-cert.pem'
    if [[ -n "${SHIBBOLETH_SP_SIGNING_CERT}" ]]; then
        cp "${SHIBBOLETH_SP_SIGNING_CERT}" "${saml_file}"
        chown "${owner}" "${saml_file}"
        chmod 0644 "${saml_file}"
    fi

    saml_file='/etc/shibboleth/sp-signing-key.pem'
    if [[ -n "${SHIBBOLETH_SP_SIGNING_PRIVKEY}" ]]; then
        cp "${SHIBBOLETH_SP_SIGNING_PRIVKEY}" "${saml_file}"
        chown "${owner}" "${saml_file}"
        chmod 0600 "${saml_file}"
    fi

    saml_file='/etc/shibboleth/sp-encrypt-cert.pem'
    if [[ -n "${SHIBBOLETH_SP_ENCRYPT_CERT}" ]]; then
        cp "${SHIBBOLETH_SP_ENCRYPT_CERT}" "${saml_file}"
        chown "${owner}" "${saml_file}"
        chmod 0644 "${saml_file}"
    fi

    saml_file='/etc/shibboleth/sp-encrypt-key.pem'
    if [[ -n "${SHIBBOLETH_SP_ENCRYPT_PRIVKEY}" ]]; then
        cp "${SHIBBOLETH_SP_ENCRYPT_PRIVKEY}" "${saml_file}"
        chown "${owner}" "${saml_file}"
        chmod 0600 "${saml_file}"
    fi
}

##########################################
# Manage UID and GID on files
# Globals:
#   None
# Arguments:
#   None
# Returns:
#   None
##########################################
function comanage_shibboleth_sp_utils::manage_uid_gid() {

    local owner
    local ownership
    local not_readable

    # A deployer may make their own mapping between the shibd username
    # and the UID, and between the shibd group and GID, so before starting 
    # make sure files have the correct ownership and group. 

    not_readable='/tmp/shibd-not-readable'

    if [[ -e '/etc/debian_version' ]]; then
        owner='_shibd'
        ownership="${owner}:${owner}"

        chown "${ownership}" /etc/shibboleth/sp-cert.pem > /dev/null 2>&1
        chown "${ownership}" /etc/shibboleth/sp-key.pem > /dev/null 2>&1

        chown "${ownership}" /etc/shibboleth/sp-signing-cert.pem > /dev/null 2>&1
        chown "${ownership}" /etc/shibboleth/sp-signing-key.pem > /dev/null 2>&1

        chown "${ownership}" /etc/shibboleth/sp-encrypt-cert.pem > /dev/null 2>&1
        chown "${ownership}" /etc/shibboleth/sp-encrypt-key.pem > /dev/null 2>&1

        chown "${ownership}" /opt/shibboleth-sp/var > /dev/null 2>&1
        chown "${ownership}" /opt/shibboleth-sp/var > /dev/null 2>&1
        chown "${ownership}" /opt/shibboleth-sp/var/run > /dev/null 2>&1
        chown "${ownership}" /opt/shibboleth-sp/var/run/shibboleth > /dev/null 2>&1
        chown "${ownership}" /opt/shibboleth-sp/var/run/shibboleth/shibd.sock > /dev/null 2>&1
        chown "${ownership}" /opt/shibboleth-sp/var/log > /dev/null 2>&1
        chown "${ownership}" /opt/shibboleth-sp/var/log/shibboleth > /dev/null 2>&1
        chown "${ownership}" /opt/shibboleth-sp/var/log/shibboleth/transaction.log > /dev/null 2>&1
        chown "${ownership}" /opt/shibboleth-sp/var/log/shibboleth/signature.log > /dev/null 2>&1
        chown "${ownership}" /opt/shibboleth-sp/var/log/shibboleth/shibd_warn.log > /dev/null 2>&1
        chown "${ownership}" /opt/shibboleth-sp/var/log/shibboleth/shibd.log > /dev/null 2>&1
        chown "${ownership}" /opt/shibboleth-sp/var/log/shibboleth-www > /dev/null 2>&1
        chown "${ownership}" /opt/shibboleth-sp/var/cache > /dev/null 2>&1
        chown "${ownership}" /opt/shibboleth-sp/var/cache/shibboleth > /dev/null 2>&1
    elif [[ -e '/etc/centos-release' ]]; then
        owner='shibd'
        ownership="${owner}:${owner}"

        chown "${ownership}" /etc/shibboleth/sp-cert.pem > /dev/null 2>&1
        chown "${ownership}" /etc/shibboleth/sp-key.pem > /dev/null 2>&1

        chown "${ownership}" /etc/shibboleth/sp-signing-cert.pem > /dev/null 2>&1
        chown "${ownership}" /etc/shibboleth/sp-signing-key.pem > /dev/null 2>&1

        chown "${ownership}" /etc/shibboleth/sp-encrypt-cert.pem > /dev/null 2>&1
        chown "${ownership}" /etc/shibboleth/sp-encrypt-key.pem > /dev/null 2>&1
    fi

    # Warn about any files the shibd user cannot read.
    sudo -u "${owner}" find /etc/shibboleth ! -readable > "${not_readable}" 2>/dev/null
    if [[ -s "${not_readable}" ]]; then
        echo "WARNING: the following files are not readable by ${owner}"
        cat "${not_readable}"
        echo ""
    fi

    rm -f "${not_readable}" > /dev/null 2>&1
}

##########################################
# Exec to start and become Shibboleth SP shibd
# Globals:
#   None
# Arguments:
#   Command and arguments to exec
# Returns:
#   Does not return
##########################################
function comanage_shibboleth_sp_utils::exec_shibboleth_sp_daemon() {

    local user
    local group
    local shibd_daemon
    local config
    local pidfile

    comanage_shibboleth_sp_utils::consume_injected_environment

    comanage_shibboleth_sp_utils::prepare_shibboleth2xml

    comanage_shibboleth_sp_utils::prepare_saml_cert_key

    comanage_shibboleth_sp_utils::manage_uid_gid

    config='/etc/shibboleth/shibboleth2.xml'
    pidfile='/var/run/shibboleth/shibd.pid'

    if [[ -e '/etc/debian_version' ]]; then
        user='_shibd'
        group='_shibd'
        shibd_daemon='/opt/shibboleth-sp/sbin/shibd'
    elif [[ -e '/etc/centos-release' ]]; then
        user='shibd'
        group='shibd'
        shibd_daemon='/usr/sbin/shibd'
        export LD_LIBRARY_PATH=/opt/shibboleth/lib64
    fi

    exec "${shibd_daemon}" -f -u "${user}" -g "${group}" -c "${config}" -p "${pidfile}" -F
}
