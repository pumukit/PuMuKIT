ARG PHP_VERSION=7.2
ARG NGINX_VERSION=1.15

FROM php:${PHP_VERSION}-fpm-alpine AS pumukit_backend
MAINTAINER Pablo Nieto, pnieto@teltek.es


ARG APCU_VERSION=5.1.12
ARG PHP_MONGODB_VERSION=1.5.3
ARG XDEBUG_VERSION=2.6.1


RUN apk add --no-cache \
		acl \
		file \
		gettext \
		git \
;

RUN set -eux; \
 	apk add --no-cache --virtual .build-deps \
 		$PHPIZE_DEPS \
 		icu-dev \
		openldap-dev \
		gettext-dev \
		libpng-dev \
		expat-dev \
 		libzip-dev \
 		zlib-dev \
		libxml2-dev \
		libxslt-dev \
 	; \
 	\
 	docker-php-ext-configure zip --with-libzip; \
 	docker-php-ext-install -j$(nproc) \
 		intl \
		exif \
		intl \
		ldap \
		gettext \
		pcntl \
		gd \
		shmop \
		sockets \
		sysvmsg \
		sysvsem \
		sysvshm \
 		zip \
		wddx \
		xsl \
 	; \
 	pecl install \
 		apcu-${APCU_VERSION} \
		mongodb-${PHP_MONGODB_VERSION} \
		xdebug-${XDEBUG_VERSION} \
 	; \
 	pecl clear-cache ;\
 	docker-php-ext-enable \
 		apcu \
 		opcache \
		mongodb \
 	; \
	\
	runDeps="$( \
            scanelf --needed --nobanner --format '%n#p' --recursive /usr/local/lib/php/extensions \
                | tr ',' '\n' \
                | sort -u \
                | awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
            )"; \
	apk add --no-cache --virtual .api-phpexts-rundeps $runDeps; \
	\
	apk del .build-deps


COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY doc/docker/php/php.ini /usr/local/etc/php/php.ini

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN set -eux; \
    composer global require "rubenrua/symfony-clean-tags-composer-plugin" --prefer-dist --no-progress --no-suggest --classmap-authoritative; \
    composer clear-cache
    
ENV PATH="${PATH}:/root/.composer/vendor/bin"

WORKDIR /srv/pumukit

# default build for production
ARG APP_ENV=prod

# copy only specifically what we need
COPY app app/
COPY bin bin/
COPY doc doc/
COPY web web/
COPY src src/
COPY composer.json ./
COPY composer.lock ./
COPY doc/docker/pumukit/parameters.yml app/config/parameters.yml

RUN set -eux; \
    composer install -a -n --no-scripts; \
    composer clear-cache

COPY doc/docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]


FROM nginx:${NGINX_VERSION}-alpine AS pumukit_frontend

RUN apk add --no-cache openssl

# Use this self-generated certificate only in dev, IT IS NOT SECURE!
RUN openssl genrsa -des3 -passout pass:NotSecure -out cert.pass.key 2048
RUN openssl rsa -passin pass:NotSecure -in cert.pass.key -out cert.key
RUN rm cert.pass.key
RUN openssl req -new -passout pass:NotSecure -key cert.key -out cert.csr \
    -subj '/C=SS/ST=SS/L=Gotham City/O=API Platform Dev/CN=localhost'
RUN openssl x509 -req -sha256 -days 365 -in cert.csr -signkey cert.key -out cert.crt


COPY doc/docker/nginx/default.conf /etc/nginx/conf.d/default.conf

WORKDIR /srv/pumukit

COPY --from=pumukit_backend /srv/pumukit/web web/



# # Set default php configuracion
# RUN echo -e "\ndate.timezone = Europe/Madrid" >> /etc/php5/cli/php.ini && \
#     echo -e "\ndate.timezone = Europe/Madrid" >> /etc/php5/fpm/php.ini && \
#     sed -i 's/memory_limit = .*/memory_limit = 512M/' /etc/php5/cli/php.ini && \
#     sed -i 's/memory_limit = .*/memory_limit = 512M/' /etc/php5/fpm/php.ini && \
#     sed -i "/post_max_size =/c\post_max_size = 2000M" /etc/php5/cli/php.ini && \
#     sed -i "/upload_max_filesize =/c\upload_max_filesize = 2000M" /etc/php5/cli/php.ini && \
#     sed -i "/post_max_size =/c\post_max_size = 2000M" /etc/php5/fpm/php.ini && \
#     sed -i "/upload_max_filesize =/c\upload_max_filesize = 2000M" /etc/php5/fpm/php.ini

