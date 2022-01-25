ARG PHP_VERSION=7.4
ARG ALPINE_VERSION=3.11

FROM php:${PHP_VERSION}-fpm-alpine${ALPINE_VERSION} as base
MAINTAINER Pablo Nieto, pnieto@teltek.es

ARG APCU_VERSION=5.1.19
ARG PHP_MONGODB_VERSION=1.9.0
ARG XDEBUG_VERSION=3.0.1

ENV PHP_FPM_PM="dynamic" \
	PHP_FPM_MAX_CHILDREN="5" \ 
	PHP_FPM_START_SERVERS="2" \
	PHP_FPM_MIN_SPARE_SERVERS="1" \
	PHP_FPM_MAX_SPARE_SERVERS="2" \
	PHP_FPM_MAX_REQUESTS="1000" \
	PHP_MAX_EXECUTION_TIME=30 \
	PHP_MEMORY_LIMIT=512M \
	PHP_UPLOAD_MAX_FILESIZE=10G \
	PHP_UPLOAD_TMP_DIR=/tmp \
	PHP_DEFAULT_LOCALE=es \
	PHP_DEFAULT_CHARSET=UTF-8

ENV PHP_FPM_PM="dynamic" \
	PHP_FPM_MAX_CHILDREN="5" \ 
	PHP_FPM_START_SERVERS="2" \
	PHP_FPM_MIN_SPARE_SERVERS="1" \
	PHP_FPM_MAX_SPARE_SERVERS="2" \
	PHP_FPM_MAX_REQUESTS="1000" \
	PHP_MAX_EXECUTION_TIME=30 \
	PHP_MEMORY_LIMIT=512M \
	PHP_UPLOAD_MAX_FILESIZE=10G \
	PHP_UPLOAD_TMP_DIR=/tmp \
	PHP_DEFAULT_LOCALE=es \
	PHP_DEFAULT_CHARSET=UTF-8

RUN apk  add --no-cache \
    	    --update \
	     	libgcc \
			libstdc++ \
			ca-certificates \
			libcrypto1.1 \
			libssl1.1 \
			libgomp \
			expat \
			python \
			py-setuptools \
			py-argparse \
			py2-pip \
			grep \
;

RUN pip install google-api-python-client

COPY --from=teltek/ffmpeg /usr/local /usr/local


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
 	docker-php-ext-configure zip; \
	freetype-config --cflags; \
     	mkdir -p /opt/ffmpeg/include; \
	ln -s /usr/include/freetype2 /opt/ffmpeg/include/freetype2; \
	docker-php-ext-configure gd \
	 	--with-freetype \
		--with-jpeg ; \
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
COPY php-cli.ini /usr/local/etc/php/php-cli.ini


COPY ./www.conf /usr/local/etc/php-fpm.d/www.conf



COPY ./www.conf /usr/local/etc/php-fpm.d/www.conf


# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1

# Download and install pumukit to get the composer cache and improve build times
RUN git clone https://github.com/pumukit/pumukit /srv/pumukit; \
	cd /srv/pumukit; \
	git checkout master; \
	composer install -a -n --no-scripts
RUN rm -rf /srv/pumukit

ENV PATH="${PATH}:/root/.composer/vendor/bin"

WORKDIR /srv/pumukit
