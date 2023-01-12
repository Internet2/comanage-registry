# COmanage Registry Dockerfile
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
ARG COMANAGE_REGISTRY_SHIBBOLETH_SP_VERSION="3.4.1"
ARG COMANAGE_REGISTRY_SHIBBOLETH_SP_BASE_IMAGE_VERSION=1

FROM comanage-registry-shibboleth-sp-base:${COMANAGE_REGISTRY_SHIBBOLETH_SP_VERSION}-${COMANAGE_REGISTRY_SHIBBOLETH_SP_BASE_IMAGE_VERSION} AS shib-base

FROM comanage-registry-base:${COMANAGE_REGISTRY_VERSION}-${COMANAGE_REGISTRY_BASE_IMAGE_VERSION} AS comanage

ARG COMANAGE_REGISTRY_BASE_IMAGE_VERSION
ENV COMANAGE_REGISTRY_BASE_IMAGE_VERSION ${COMANAGE_REGISTRY_BASE_IMAGE_VERSION}
LABEL comanage_registry_base_image_version=${COMANAGE_REGISTRY_BASE_IMAGE_VERSION}

ARG COMANAGE_REGISTRY_SHIBBOLETH_SP_VERSION
ENV COMANAGE_REGISTRY_SHIBBOLETH_SP_VERSION ${COMANAGE_REGISTRY_SHIBBOLETH_SP_VERSION}
LABEL comanage_registry_shibboleth_sp_version=${COMANAGE_REGISTRY_SHIBBOLETH_SP_VERSION}

ARG COMANAGE_REGISTRY_SHIBBOLETH_SP_BASE_IMAGE_VERSION
ENV COMANAGE_REGISTRY_SHIBBOLETH_SP_BASE_IMAGE_VERSION ${COMANAGE_REGISTRY_SHIBBOLETH_SP_BASE_IMAGE_VERSION}
LABEL comanage_registry_shibboleth_sp_base_image_version ${COMANAGE_REGISTRY_SHIBBOLETH_SP_BASE_IMAGE_VERSION}

RUN apt-get install -y --no-install-recommends supervisor \
      && mkdir -p /var/log/supervisor

COPY --from=shib-base /opt/shibboleth-sp /opt/shibboleth-sp/
COPY --from=shib-base /opt/shibboleth-sp/etc/shibboleth/shib2.load /etc/apache2/mods-available/

RUN /usr/sbin/useradd --system _shibd \
      && mkdir -p /var/run/shibboleth \
      && chown _shibd:_shibd /var/run/shibboleth \
      && chown -R _shibd:_shibd /opt/shibboleth-sp/var \
      && cp -a /opt/shibboleth-sp/etc/shibboleth /etc/shibboleth \
      && rm -f /etc/shibboleth/shibboleth2.xml \
      && chown _shibd:_shibd /etc/shibboleth/sp-signing-cert.pem \
      && chown _shibd:_shibd /etc/shibboleth/sp-signing-key.pem \
      && chown _shibd:_shibd /etc/shibboleth/sp-encrypt-cert.pem \
      && chown _shibd:_shibd /etc/shibboleth/sp-encrypt-key.pem \
      && cd /opt/shibboleth-sp/etc \
      && rm -rf shibboleth \
      && ln -s /etc/shibboleth shibboleth \
      && a2enmod shib2

COPY container/registry/shibboleth-sp-supervisor/supervisord.conf /usr/local/etc/supervisord.conf
COPY container/registry/shibboleth-sp-supervisor/docker-comanage-shibboleth-sp-entrypoint /usr/local/bin/

ENTRYPOINT ["/usr/bin/supervisord", "-c", "/usr/local/etc/supervisord.conf"]
