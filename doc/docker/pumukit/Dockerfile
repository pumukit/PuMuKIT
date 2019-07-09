ARG PHP_VERSION=7.2
ARG ALPINE_VERSION=3.8

FROM php:${PHP_VERSION}-fpm-alpine${ALPINE_VERSION} as base
MAINTAINER Pablo Nieto, pnieto@teltek.es

ARG APCU_VERSION=5.1.12
ARG PHP_MONGODB_VERSION=1.5.3
ARG XDEBUG_VERSION=2.6.1

RUN apk  add --no-cache \
    	     		--update \
	     		libgcc \
			libstdc++ \
			ca-certificates \
			libcrypto1.0 \
			libssl1.0 \
			libgomp \
			expat \
			python \
			py-setuptools \
			py-argparse \
			py2-pip \
			py-gflags \
;

RUN pip install google-api-python-client==1.2

COPY --from=jrottenberg/ffmpeg:4.0-alpine /usr/local /usr/local


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
		jpeg-dev \
		freetype-dev \
 	; \
 	\
 	docker-php-ext-configure zip --with-libzip; \
	freetype-config --cflags; \
     	mkdir -p /opt/ffmpeg/include; \
	ln -s /usr/include/freetype2 /opt/ffmpeg/include/freetype2; \
	docker-php-ext-configure gd \
	 	--with-freetype-dir=/usr/include/ \
		--with-jpeg-dir=/usr/include/ \
		-with-png-dir=/usr/include; \
 	docker-php-ext-install -j$(nproc) \
 		intl \
		exif \
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
COPY php.ini /usr/local/etc/php/php.ini

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN set -eux; \
    composer global require "rubenrua/symfony-clean-tags-composer-plugin" --prefer-dist --no-progress --no-suggest --classmap-authoritative

# Download and install pumukit to get the composer cache and improve build times
RUN git clone https://github.com/pumukit/pumukit /srv/pumukit; \
cd /srv/pumukit; \
composer install -a -n --no-scripts
RUN rm -rf /srv/pumukit

ENV PATH="${PATH}:/root/.composer/vendor/bin"

WORKDIR /srv/pumukit
