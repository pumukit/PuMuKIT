ARG PHP_VERSION=7.2
ARG NGINX_VERSION=1.15
ARG MONGODB_VERSION=3.6

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




# # Set default php configuracion
# RUN echo -e "\ndate.timezone = Europe/Madrid" >> /etc/php5/cli/php.ini && \
#     echo -e "\ndate.timezone = Europe/Madrid" >> /etc/php5/fpm/php.ini && \
#     sed -i 's/memory_limit = .*/memory_limit = 512M/' /etc/php5/cli/php.ini && \
#     sed -i 's/memory_limit = .*/memory_limit = 512M/' /etc/php5/fpm/php.ini && \
#     sed -i "/post_max_size =/c\post_max_size = 2000M" /etc/php5/cli/php.ini && \
#     sed -i "/upload_max_filesize =/c\upload_max_filesize = 2000M" /etc/php5/cli/php.ini && \
#     sed -i "/post_max_size =/c\post_max_size = 2000M" /etc/php5/fpm/php.ini && \
#     sed -i "/upload_max_filesize =/c\upload_max_filesize = 2000M" /etc/php5/fpm/php.ini

