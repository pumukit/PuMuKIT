FROM teltek/pumukit-base:4.x

# default build for production
ARG APP_ENV=prod
ARG PHP_MEMORY_LIMIT=512M

# copy the code into the docker
COPY --chown=www-data:www-data . ./

# load environment variables
RUN source .env

RUN set -eux; \
    mkdir -p var/cache var/log var/sessions && \
    composer install --prefer-dist --no-scripts --no-progress --classmap-authoritative --no-interaction && \
    chown -R www-data var && \
    php bin/console a:i

COPY doc/docker/pumukit/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

## Add the wait script to the image
ADD https://github.com/ufoscout/docker-compose-wait/releases/download/2.4.0/wait /wait
RUN chmod +x /wait

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]
