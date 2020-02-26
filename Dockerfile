FROM teltek/pumukit-base:3.3.x

# default build for production
ARG APP_ENV=prod
ARG PHP_MEMORY_LIMIT=512M


# copy the code into the docker
COPY --chown=www-data:www-data . ./

# load environment variables
RUN source doc/docker/.env

RUN set -eux; \
    composer install -a -n; \
    php vendor/sensio/distribution-bundle/Resources/bin/build_bootstrap.php; \
    php app/console a:i
    
COPY doc/docker/pumukit/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

## Add the wait script to the image
ADD https://github.com/ufoscout/docker-compose-wait/releases/download/2.4.0/wait /wait
RUN chmod +x /wait

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]
