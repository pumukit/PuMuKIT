#!/bin/sh
set -e

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
    set -- php-fpm "$@"
fi

/wait

if [ "$1" = 'php-fpm' ] || [ "$1" = 'bin/console' ]; then
    mkdir -p var/cache var/log var/sessions
    setfacl -R -m u:www-data:rwX -m u:"$(whoami)":rwX var/cache var/log var/sessions public/storage public/uploads
    setfacl -dR -m u:www-data:rwX -m u:"$(whoami)":rwX var/cache var/log var/sessions public/storage public/uploads

    if [ "$APP_ENV" != 'prod' ]; then
        composer install --prefer-dist --no-scripts --no-progress --no-suggest --classmap-authoritative --no-interaction
        bin/console doctrine:mongodb:schema:create
	    bin/console pumukit:init:repo all --force
    	if [ "$AUTOCREATE_PUMUKIT_USER" == 'true' ]; then
	        set +e
	            php bin/console fos:user:create $PUMUKIT_USER $PUMUKIT_USER_MAIL $PUMUKIT_PASS --super-admin || true
	        set -e
	    fi
    fi
fi

exec docker-php-entrypoint "$@"
