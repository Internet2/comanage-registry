# Dockerfile for Shibboleth SP for COmanage Registry
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
FROM debian:11-slim

ARG LOG4SHIB_VERSION
ENV LOG4SHIB_VERSION=${LOG4SHIB_VERSION:-2.0.1}

ARG XERCESC_VERSION
ENV XERCESC_VERSION=${XERCESC_VERSION:-3.2.4}

ARG XMLSECC_VERSION
ENV XMLSECC_VERSION=${XMLSECC_VERSION:-2.0.4}

ARG XMLTOOLING_VERSION
ENV XMLTOOLING_VERSION=${XMLTOOLING_VERSION:-3.2.2}

ARG OPENSAMLC_VERSION
ENV OPENSAMLC_VERSION=${OPENSAMLC_VERSION:-3.2.1}

ARG SHIBBOLETH_SP_VERSION
ENV SHIBBOLETH_SP_VERSION=${SHIBBOLETH_SP_VERSION:-3.4.1}

RUN apt-get update \
      && apt-get install -y --no-install-recommends \
            apache2 \
            apache2-dev \
            ca-certificates \
            file \
            gcc \
            g++ \
            libboost-dev \
            libcurl4-openssl-dev \
            libssl-dev \
            libssl1.1 \
            make \
            pkg-config \
            wget \
            zlib1g-dev


RUN LOG4SHIB_URL="https://shibboleth.net/downloads/log4shib/${LOG4SHIB_VERSION}/log4shib-${LOG4SHIB_VERSION}.tar.gz" \
      && wget -O log4shib.tar.gz "${LOG4SHIB_URL}" \
      && mkdir -p src \
      && tar -zxf log4shib.tar.gz -C src --strip-components=1 \
      && rm -f log4shib.tar.gz \
      && cd src \
      && LD_FLAGS=-lpthread CXXFLAGS="-fPIC" ./configure --disable-static \
            --disable-doxygen \ 
            --prefix=/opt/shibboleth-sp \
            --build=x86_64 \
      && LD_FLAGS=-lpthread make \
      && make -j 2 install \
      && cd .. \
      && rm -r src 

RUN XERCESC_DIST_URLS="\
    https://mirrors.ocf.berkeley.edu/apache/xerces/c/3/sources/xerces-c-${XERCESC_VERSION}.tar.gz \
    http://www.gtlib.gatech.edu/pub/apache//xerces/c/3/sources/xerces-c-${XERCESC_VERSION}.tar.gz \
    http://apache.mirrors.spacedump.net/xerces/c/3/sources/xerces-c-${XERCESC_VERSION}.tar.gz \
    http://apache.mirror.serversaustralia.com.au/xerces/c/3/sources/xerces-c-${XERCESC_VERSION}.tar.gz" \
    && for distUrl in ${XERCESC_DIST_URLS}; \
      do \
        if wget -nv -O xerces.tar.gz "${distUrl}"; then \
            break; \
        fi; \
      done \
      && mkdir -p src \
      && tar -zxf xerces.tar.gz -C src --strip-components=1 \
      && rm -f xerces.tar.gz \
      && cd src \
      && ./configure --prefix=/opt/shibboleth-sp \
      && make -j 2 \
      && make install \
      && cd .. \
      && rm -r src 

RUN XMLSECC_DIST_URLS="\
    https://mirrors.ocf.berkeley.edu/apache/santuario/c-library/xml-security-c-${XMLSECC_VERSION}.tar.gz \
    https://mirrors.ocf.berkeley.edu/apache/santuario/c-library/xml-security-c-${XMLSECC_VERSION}.tar.gz \
    http://www.gtlib.gatech.edu/pub/apache/santuario/c-library/xml-security-c-${XMLSECC_VERSION}.tar.gz \
    http://apache.mirrors.spacedump.net/santuario/c-library/xml-security-c-${XMLSECC_VERSION}.tar.gz \
    http://apache.mirror.serversaustralia.com.au/santuario/c-library/xml-security-c-${XMLSECC_VERSION}.tar.gz" \
    && for distUrl in ${XMLSECC_DIST_URLS}; \
      do \
        if wget -nv -O xmlsecc.tar.gz "${distUrl}"; then \
            break; \
        fi; \
      done \
      && mkdir -p src \
      && tar -zxf xmlsecc.tar.gz -C src --strip-components=1 \
      && rm -f xmlsecc.tar.gz \
      && cd src \
      && PKG_CONFIG_PATH=/opt/shibboleth-sp/lib/pkgconfig ./configure --prefix=/opt/shibboleth-sp \
            --with-openssl=/usr \
            --disable-static \
            --without-xalan \
      && make -j 2 \
      && make install \
      && cd .. \
      && rm -r src 

RUN XMLTOOLING_URL="https://shibboleth.net/downloads/c++-opensaml/${OPENSAMLC_VERSION}/xmltooling-${XMLTOOLING_VERSION}.tar.gz" \
      && wget -O xmltooling.tar.gz "${XMLTOOLING_URL}" \
      && mkdir -p src \
      && tar -zxf xmltooling.tar.gz -C src --strip-components=1 \
      && rm -f xmltooling.tar.gz \
      && cd src \
      && CXXFLAGS="-fPIC" \
            PKG_CONFIG_PATH=/opt/shibboleth-sp/lib/pkgconfig \
            ./configure --prefix=/opt/shibboleth-sp \
            --with-log4shib=/opt/shibboleth-sp \
            -C \
      && make -j 2 \
      && make install \
      && cd .. \
      && rm -r src 

RUN OPENSAMLC_URL="https://shibboleth.net/downloads/c++-opensaml/${OPENSAMLC_VERSION}/opensaml-${OPENSAMLC_VERSION}.tar.gz" \
      && wget -O opensamlc.tar.gz "${OPENSAMLC_URL}" \
      && mkdir -p src \
      && tar -zxf opensamlc.tar.gz -C src --strip-components=1 \
      && rm -f opensamlc.tar.gz \
      && cd src \
      && PKG_CONFIG_PATH=/opt/shibboleth-sp/lib/pkgconfig \
            ./configure --prefix=/opt/shibboleth-sp \
            --with-log4shib=/opt/shibboleth-sp \
            -C \
      && make -j 2 \
      && make install \
      && cd .. \
      && rm -r src 

RUN SHIBBOLETH_SP_URL="https://shibboleth.net/downloads/service-provider/${SHIBBOLETH_SP_VERSION}/shibboleth-sp-${SHIBBOLETH_SP_VERSION}.tar.gz" \
      && wget -O shibboleth-sp.tar.gz "${SHIBBOLETH_SP_URL}" \
      && mkdir -p src \
      && tar -zxf shibboleth-sp.tar.gz -C src --strip-components=1 \
      && rm -f shibboleth-sp.tar.gz \
      && cd src \
      && CXXFLAGS="-Wno-unused-parameter" \
            PKG_CONFIG_PATH=/opt/shibboleth-sp/lib/pkgconfig \
            ./configure --prefix=/opt/shibboleth-sp \
            --with-log4shib=/opt/shibboleth-sp \
            --with-saml=/opt/shibboleth-sp \
            --with-xerces=/opt/shibboleth-sp \
            --with-xmlsec=/opt/shibboleth-sp \
            --with-xmltooling=/opt/shibboleth-sp \
            --enable-apache-24 \
            --with-apxs24=/usr/bin/apxs \
            --disable-adfs \
            --disable-obc \
      && make -j 2 \
      && make install \
      && cd .. \
      && rm -r src 

COPY container/shibboleth-sp-base/shib2.load /opt/shibboleth-sp/etc/shibboleth/shib2.load
COPY container/shibboleth-sp-base/shibboleth2.xml.template /opt/shibboleth-sp/etc/shibboleth/shibboleth2.xml.template
COPY container/shibboleth-sp-base/shibd.logger /opt/shibboleth-sp/etc/shibboleth/shibd.logger
COPY container/shibboleth-sp-base/native.logger /opt/shibboleth-sp/etc/shibboleth/native.logger
COPY container/shibboleth-sp-base/console.logger /opt/shibboleth-sp/etc/shibboleth/console.logger

RUN apt-get purge -y \
         apache2-dev \
         file \
         gcc \
         g++ \
         libboost-dev \
         libcurl4-openssl-dev \
         libssl-dev \
         make \
         pkg-config \
      && apt-get clean
