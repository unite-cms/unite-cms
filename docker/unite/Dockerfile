#
# unite-cms Nginx image PHP 7.2 / PHP FPM
#

FROM unitecms/unite-cms-docker-centos

ENV PHP_VERSION=7.2 \
    PHP_VER_SHORT=72 \
    NAME=nginx-php

ENV SUMMARY="unite-cms nginx / php pfm image" \
    DESCRIPTION=""

LABEL summary="${SUMMARY}" \
      description="${DESCRIPTION}" \
      io.k8s.description="${DESCRIPTION}" \
      io.k8s.display-name="Nginx 2.4 with PHP ${PHP_VERSION}" \
      io.openshift.expose-services="8080:http" \
      io.openshift.tags="builder,${NAME},${NAME}${PHP_VER_SHORT},rh-${NAME}${PHP_VER_SHORT}" \
      name="unitecms/unite-cms-docker-nginx" \
      version="${PHP_VERSION}" \
      maintainer="www.unitecms.io <stefan@unite.co.at>"

# Install required repos, update, and then install PHP-FPM
RUN yum install -y epel-release \ 
        http://rpms.remirepo.net/enterprise/remi-release-7.rpm  \
        yum-utils && \
    yum-config-manager --enable remi-php72 && \
    yum install -y \
        php-bcmath \
        php-cli \
        php-fpm \
        php-mysqlnd \
        php-xml \
        php-gd \
        php-mcrypt \
        php-mbstring \
        php-opcache \
        php-pdo

# install nginx
RUN yum install -y yum-utils  && \
    INSTALL_PKGS="nginx" && \
    yum install -y --setopt=tsflags=nodocs $INSTALL_PKGS && \
    rpm -V $INSTALL_PKGS

# Installing supervisor
RUN yum install -y python-setuptools
RUN easy_install pip
RUN pip install supervisor

# cleanup to reduce size
RUN yum clean all && \
    rm -rf /var/cache/yum

COPY ./root/ /

RUN chmod +x /usr/bin/*

WORKDIR /app

# install composer and create composer cache dir
RUN cd /tmp
RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer
RUN mkdir -p /.composer
RUN chown -R 1001:0 /.composer && \
    chmod -R 775 /.composer

RUN container-setup && rpm-file-permissions

USER 1001

# supervisord
ENTRYPOINT ["container-entrypoint"]
CMD ["supervisord", "-n"]