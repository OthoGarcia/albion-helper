# Default Dockerfile
#
# @link     https://www.hyperf.io
# @document https://hyperf.wiki
# @contact  group@hyperf.io
# @license  https://github.com/hyperf/hyperf/blob/master/LICENSE

FROM hyperf/hyperf:8.3-alpine-v3.19-swoole
LABEL maintainer="Hyperf Developers <group@hyperf.io>" version="1.0" license="MIT" app.name="Hyperf"

##
# ---------- env settings ----------
##
# --build-arg timezone=Asia/Shanghai
ARG timezone
ARG INSTALL_XDEBUG=false


ENV TIMEZONE=${timezone:-"Asia/Shanghai"} \
    APP_ENV=prod \
    SCAN_CACHEABLE=(true)

# update
RUN set -ex \
    # show php version and extensions
    && php -v \
    && php -m \
    && php --ri swoole \
    #  ---------- some config ----------
    && cd /etc/php* \
    # - config PHP
    && { \
        echo "upload_max_filesize=128M"; \
        echo "post_max_size=128M"; \
        echo "memory_limit=1G"; \
        echo "date.timezone=${TIMEZONE}"; \
    } | tee conf.d/99_overrides.ini \
    # - config timezone
    && ln -sf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime \
    && echo "${TIMEZONE}" > /etc/timezone \
    # ---------- clear works ----------
    && rm -rf /var/cache/apk/* /tmp/* /usr/share/man \
    && echo -e "\033[42;37m Build Completed :).\033[0m\n"


# Instala o Xdebug apenas se solicitado
RUN if [ "$INSTALL_XDEBUG" = "true" ]; then \
    apk update && \
    apk add --no-cache \
        php83-pecl-xdebug \
    && echo "zend_extension=xdebug.so" > /etc/php83/conf.d/50_xdebug.ini && \
    echo "xdebug.mode=debug" >> /etc/php83/conf.d/50_xdebug.ini && \
    echo "xdebug.start_with_request=yes" >> /etc/php83/conf.d/50_xdebug.ini && \
    echo "xdebug.client_host=host.docker.internal" >> /etc/php83/conf.d/50_xdebug.ini && \
    echo "xdebug.client_port=9003" >> /etc/php83/conf.d/50_xdebug.ini; \
else \
    echo "Xdebug não será ativado" ; \
fi
WORKDIR /opt/www

# Composer Cache
# COPY ./composer.* /opt/www/
# RUN composer install --no-dev --no-scripts

COPY . /opt/www
RUN composer install --no-dev -o && php bin/hyperf.php

EXPOSE 9501

ENTRYPOINT ["php", "/opt/www/bin/hyperf.php", "server:watch"]
