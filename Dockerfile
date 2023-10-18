ARG PHP_VERSION=8.2
ARG SO_VERSION=bookworm

FROM php:${PHP_VERSION}-fpm-${SO_VERSION} as base
LABEL org.opencontainers.image.authors="Pablo Nieto, pnieto@teltek.es"

ARG APCU_VERSION=5.1.22
ARG PHP_MONGODB_VERSION=1.14.2
ARG XDEBUG_VERSION=3.2.0
ARG PHP_REDIS_VERSION=5.3.4
ARG DEBIAN_FRONTEND=noninteractive

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

USER root

RUN apt-get update \
		&& apt-get install -y --no-install-recommends \ 
		python3-pip \
		gettext \
		git \
		&& apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/* \
		&& pip install google-api-python-client --break-system-packages
		
RUN apt-get update \
		&& apt-get install -y --no-install-recommends \
		libpng-dev \
		libjpeg-dev \
		libpq-dev \
		libonig-dev \
		zip \
		libzip-dev \
		libicu-dev \
		zlib1g-dev \
		libldap-dev \
		libxml2-dev \
		libxslt1-dev \
		libfreetype-dev \
		&& docker-php-ext-configure zip \
		&& docker-php-ext-configure gd \
	 	--with-freetype \
		--with-jpeg \
		&& docker-php-ext-install \
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
		soap \
		&& pecl install apcu-${APCU_VERSION} \
		&& pecl install mongodb-${PHP_MONGODB_VERSION} \
		&& pecl install xdebug-${XDEBUG_VERSION} \
		&& pecl install redis-${PHP_REDIS_VERSION} \
		&& docker-php-ext-enable \
		apcu \
		opcache \
		mongodb \
		redis \
		&& pecl clear-cache

COPY --from=linuxserver/ffmpeg:version-6.0-cli /usr/local /usr/local

RUN \
  echo "**** install runtime ****" && \
    apt-get update && \
    apt-get install -y \
    libexpat1 \
    libglib2.0-0 \
    libgomp1 \
    libharfbuzz0b \
    libpciaccess0 \
    libv4l-0 \
    libwayland-client0 \
    libx11-6 \
    libx11-xcb1 \
    libxcb-dri3-0 \
    libxcb-shape0 \
    libxcb-xfixes0 \
    libxcb1 \
    libxext6 \
    libxfixes3 \
    libxml2 \
    ocl-icd-libopencl1 && \
  echo "**** clean up ****" && \
	apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY php.ini /usr/local/etc/php/php.ini
COPY php-cli.ini /usr/local/etc/php/php-cli.ini
COPY ./www.conf /usr/local/etc/php-fpm.d/www.conf

RUN chown -R www-data:www-data /var/www
RUN mkdir -p /srv/pumukit && chown -R www-data:www-data /srv/pumukit

USER www-data

WORKDIR /srv/pumukit

FROM base as production

# default build for production
ARG APP_ENV=prod
ARG PHP_MEMORY_LIMIT=512M

# copy the code into the docker
COPY --chown=www-data:www-data . ./

# load environment variables
RUN source .env

RUN set -eux \
    && mkdir -p var/cache var/log var/sessions \
    && composer update --prefer-dist --no-scripts --no-progress --classmap-authoritative --no-interaction \
    && php bin/console a:i

COPY doc/docker/pumukit/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

## Add the wait script to the image
ADD https://github.com/ufoscout/docker-compose-wait/releases/download/2.4.0/wait /wait
RUN chmod +x /wait