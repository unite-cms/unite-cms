#
# unite cms docker development image
#

FROM unitecms/docker-nginx-php-fpm:latest

ENV NAME="unite-cms" \
    SUMMARY="unite-cms docker image" \
    DESCRIPTION="unite-cms docker image" \
    APP_ROOT=/app \
    PATH=/app/bin:$PATH

USER root

RUN mkdir /app

WORKDIR ${APP_ROOT}

RUN rpm-file-permissions && \
    useradd -u 1001 -r -g 0 -d ${APP_ROOT} -s /sbin/nologin \
      -c "Default Application User" default && \
    chown -R 1001:0 ${APP_ROOT}

RUN chown -R 1001:0 /app
RUN fix-permissions /app

USER 1001