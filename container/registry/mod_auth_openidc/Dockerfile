# COmanage Registry Dockerfile template
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
ARG COMANAGE_REGISTRY_VERSION=develop
ARG COMANAGE_REGISTRY_BASE_IMAGE_VERSION=1
ARG MOD_AUTH_OPENIDC_VERSION

FROM comanage-registry-base:${COMANAGE_REGISTRY_VERSION}-${COMANAGE_REGISTRY_BASE_IMAGE_VERSION} AS comanage

FROM debian:bookworm-slim AS building

ARG MOD_AUTH_OPENIDC_VERSION
ENV MOD_AUTH_OPENIDC_VERSION ${MOD_AUTH_OPENIDC_VERSION:-2.4.12.2}

RUN apt-get update \
      && apt-get install -y --no-install-recommends \
        apache2 \
        apache2-dev \
        automake \
        ca-certificates \
        libcjose0 \
        libcjose-dev \
        libcurl4-openssl-dev \
        libjansson-dev \
        libpcre3-dev \
        libssl-dev \
        pkg-config \
        wget 

RUN MOD_AUTH_OPENIDC_URL="https://github.com/zmartzone/mod_auth_openidc/releases/download/v${MOD_AUTH_OPENIDC_VERSION}/mod_auth_openidc-${MOD_AUTH_OPENIDC_VERSION}.tar.gz" \
      && mkdir -p /tmp/mod_auth_openidc \
      && wget -O mod_auth_openidc.tar.gz "${MOD_AUTH_OPENIDC_URL}" \
      && tar -zxf mod_auth_openidc.tar.gz -C /tmp/mod_auth_openidc --strip-components=1 \
      && cd /tmp/mod_auth_openidc \
      && ./configure --with-apxs2=`which apxs2` \
      && make \
      && make install 

FROM comanage

ARG COMANAGE_REGISTRY_BASE_IMAGE_VERSION
ENV COMANAGE_REGISTRY_BASE_IMAGE_VERSION ${COMANAGE_REGISTRY_BASE_IMAGE_VERSION}
LABEL comanage_registry_base_image_version=${COMANAGE_REGISTRY_BASE_IMAGE_VERSION}

ARG MOD_AUTH_OPENIDC_VERSION
ENV MOD_AUTH_OPENIDC_VERSION ${MOD_AUTH_OPENIDC_VERSION:-2.4.10}
LABEL mod_auth_openidc_version ${MOD_AUTH_OPENIDC_VERSION}

RUN apt-get update \
        && apt-get install -y --no-install-recommends \
          libcjose0

COPY --from=building /usr/lib/apache2/modules/mod_auth_openidc.so /usr/lib/apache2/modules/mod_auth_openidc.so

RUN echo "LoadModule auth_openidc_module /usr/lib/apache2/modules/mod_auth_openidc.so" > /etc/apache2/mods-available/auth_openidc.load \
      && a2enmod auth_openidc 

COPY container/registry/mod_auth_openidc/htaccess /srv/comanage-registry/app/webroot/.htaccess
