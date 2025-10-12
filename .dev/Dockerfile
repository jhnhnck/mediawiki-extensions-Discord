FROM php:8.4-fpm AS mediawiki

ENV TZ='America/New_York'
ENV APP_HOME='/app'
ENV LANG='en_US.UTF-8'

ARG ASSETS='.dev/assets'
ARG MEDIAWIKI_MAJOR_VERSION='1.44'
ARG MEDIAWIKI_BRANCH='REL1_44'

# system packages
RUN --mount=type=cache,sharing=locked,target=/var/lib/apt \
    set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        git \
        zsh; \
    bash -c 'mkdir -p $APP_HOME/{mediawiki,logs}';

# PHP extensions
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN --mount=type=cache,sharing=locked,target=/var/lib/apt \
    install-php-extensions \
        @composer \
        apcu \
        calendar \
        exif \
        intl \
        mysqli \
        sockets \
        wikidiff2 \
        zip;

# php-fpm configuration tweaks
RUN set -eu; \
    printf '%s\n' \
        'error_reporting = E_ALL' \
        'log_errors = On' \
        'display_errors = On' \
        'upload_max_filesize = 100M' \
        'post_max_size = 100M' \
        'max_execution_time = 60' \
            >> /usr/local/etc/php/conf.d/php-config.ini; \
    printf '%s\n' \
        'pm.max_children = 30' \
        'pm.max_requests = 200' \
        'pm.start_servers = 10' \
        'pm.min_spare_servers = 10' \
        'pm.max_spare_servers = 30' \
            >> /usr/local/etc/php-fpm.d/zz-docker.conf;

RUN set -eux; \
    usermod -d $APP_HOME www-data; \
    chown -R www-data:www-data $APP_HOME; \
    chmod -R +220 $APP_HOME;

USER www-data
WORKDIR $APP_HOME/mediawiki

# Code patches (as needed)
# COPY --chown=www-data:www-data $ASSETS/patches $APP_HOME/patches

# MediaWiki core and "included" extensions
RUN --mount=type=cache,target=$APP_HOME/.cache/composer \
    set -eux; \
    git clone --no-recurse-submodules --depth=1 --branch "$MEDIAWIKI_BRANCH" https://gerrit.wikimedia.org/r/mediawiki/core.git .; \
    git submodule update --init --recursive -- \
        skins/Vector; \
    mkdir -p trash; \
    composer update;

# assets
COPY --chown=www-data:www-data $ASSETS/LocalSettings.php $APP_HOME/mediawiki/LocalSettings.new
COPY --chown=www-data:www-data --chmod=770 $ASSETS/entry.zsh $APP_HOME/entry.zsh

# setup extension

# https://github.com/jhnhnck/mediawiki-extensions-NovaDiscord
COPY --chown=www-data:www-data \
    --exclude=.dev --exclude=vendor \
    . ./extensions/NovaDiscord/

WORKDIR $APP_HOME
CMD ["zsh", "./entry.zsh"]
