#
# unite cms docker development image
#

FROM unitecms/docker-nginx-php-fpm:latest

ENV NAME="unite-cms development" \
    SUMMARY="unite-cms docker development image" \
    DESCRIPTION="unite-cms docker development image" \
    APP_ROOT=/app

USER root

COPY ./root/ /

RUN mkdir ${APP_ROOT}

WORKDIR ${APP_ROOT}

RUN rpm-file-permissions && \
    useradd -u 1001 -r -g 0 -d ${APP_ROOT} -s /sbin/nologin \
      -c "Default Application User" default && \
    chown -R 1001:0 ${APP_ROOT} && \
    chown -R 1001:0 /etc/php-fpm.d && \
    chown -R 1001:0 /etc/nginx && \
    fix-permissions /app

USER 1001