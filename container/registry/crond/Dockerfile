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

FROM comanage-registry-base:${COMANAGE_REGISTRY_VERSION}-${COMANAGE_REGISTRY_BASE_IMAGE_VERSION}

RUN apt-get update \
      && apt-get install -y --no-install-recommends \
        busybox-syslogd \
        cron 

COPY --chown=root:root container/registry/crond/root-crontab /etc/crontab
COPY --chown=root:root container/registry/crond/www-data-crontab /srv/comanage-registry/local/crontab

RUN /usr/bin/crontab -u www-data /srv/comanage-registry/local/crontab \
      && /usr/sbin/usermod --shell /bin/bash www-data \
      && touch /etc/default/locale

COPY container/registry/crond/docker-comanage-cron-entrypoint /usr/local/bin/

ENTRYPOINT ["docker-comanage-cron-entrypoint"]

CMD ["/usr/sbin/cron", "-f", "-L", "15"]
