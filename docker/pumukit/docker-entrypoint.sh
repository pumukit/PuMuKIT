#!/bin/sh
set -e

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
    set -- php-fpm "$@"
fi

/wait

if [ "$1" = 'php-fpm' ] || [ "$1" = 'bin/console' ]; then
    mkdir -p var/cache var/log var/sessions

    if [ "$APP_ENV" != 'prod' ]; then
        composer install --prefer-dist --no-progress --classmap-authoritative --no-interaction
        set +e
        if [ "$AUTOCREATE_PUMUKIT_USER" = 'true' ]; then
            bin/console pumukit:setup --user="$PUMUKIT_USER" --email="$PUMUKIT_USER_MAIL" --password="$PUMUKIT_PASS"
        else
            bin/console pumukit:setup
        fi
        set -e
    fi

    chown -R www-data:www-data var/cache var/log var/sessions
    find public/storage public/uploads -maxdepth 1 ! -user www-data -exec chown www-data:www-data {} +
fi

exec docker-php-entrypoint "$@"
