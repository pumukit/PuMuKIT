ARG PHP_VERSION=7.2
ARG ALPINE_VERSION=3.10

FROM php:${PHP_VERSION}-fpm-alpine${ALPINE_VERSION} as base
MAINTAINER Pablo Nieto, pnieto@teltek.es

ARG APCU_VERSION=5.1.12
ARG PHP_MONGODB_VERSION=1.5.3
ARG XDEBUG_VERSION=2.6.1

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

# PHP 7.2 is EOL: rebuild PHP 7.2.34 with the security backports from
# deb.sury.org (php7.2 7.2.34-66). The patched source is repacked into
# /usr/src/php.tar.xz so docker-php-ext-install builds the extensions from it.
# See patches/php-7.2/series for the selected patches.
ENV PHP_VERSION=7.2.34 \
	PHP_URL="https://www.php.net/distributions/php-7.2.34.tar.xz" \
	PHP_ASC_URL="https://www.php.net/distributions/php-7.2.34.tar.xz.asc" \
	PHP_SHA256="409e11bc6a2c18707dfc44bc61c820ddfd81e17481470f3405ee7822d8379903"

COPY patches/php-7.2/ /usr/src/php-patches/

RUN set -eux; \
	apk add --no-cache --virtual .php-rebuild-deps \
		$PHPIZE_DEPS \
		argon2-dev \
		coreutils \
		curl-dev \
		libedit-dev \
		libsodium-dev \
		libxml2-dev \
		openssl-dev \
		sqlite-dev \
		git \
	; \
	\
	cd /usr/src; \
	rm -f php.tar.xz php.tar.xz.asc; \
	curl -fsSL -o php-src.tar.xz "$PHP_URL"; \
	echo "$PHP_SHA256 *php-src.tar.xz" | sha256sum -c -; \
	mkdir php; \
	tar -Jxf php-src.tar.xz -C php --strip-components=1; \
	rm php-src.tar.xz; \
	cd php; \
	while read -r p; do \
		git apply --verbose --exclude='*/tests/*' "/usr/src/php-patches/$p"; \
	done < /usr/src/php-patches/series; \
	cd /usr/src; \
	tar -cJf php.tar.xz php; \
	rm -rf php /usr/src/php-patches; \
	\
	export CFLAGS="$PHP_CFLAGS" CPPFLAGS="$PHP_CPPFLAGS" LDFLAGS="$PHP_LDFLAGS"; \
	docker-php-source extract; \
	cd /usr/src/php; \
	gnuArch="$(dpkg-architecture --query DEB_BUILD_GNU_TYPE)"; \
	./configure \
		--build="$gnuArch" \
		--with-config-file-path="$PHP_INI_DIR" \
		--with-config-file-scan-dir="$PHP_INI_DIR/conf.d" \
		--enable-option-checking=fatal \
		--with-mhash \
		--enable-ftp \
		--enable-mbstring \
		--enable-mysqlnd \
		--with-password-argon2 \
		--with-sodium=shared \
		--with-pdo-sqlite=/usr \
		--with-sqlite3=/usr \
		--with-curl \
		--with-libedit \
		--with-openssl \
		--with-zlib \
		${PHP_EXTRA_CONFIGURE_ARGS:-} \
	; \
	make -j "$(nproc)"; \
	find -type f -name '*.a' -delete; \
	make install; \
	find /usr/local/bin /usr/local/sbin -type f -perm +0111 -exec strip --strip-all '{}' + || true; \
	make clean; \
	cp -v php.ini-* "$PHP_INI_DIR/"; \
	cd /; \
	docker-php-source delete; \
	apk del --no-network .php-rebuild-deps; \
	php -v | grep -F 'PHP 7.2.34'

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
			py-gflags \
;

RUN pip install google-api-python-client==1.2

COPY --from=teltek/ffmpeg@sha256:37972c24e41d5a9a2b01fcba28b4ee9dd4dfd45856eef1f46a29e387c1e892ad /usr/local /usr/local


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
		soap \
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


COPY --from=composer:2.6.5 /usr/bin/composer /usr/bin/composer
COPY php.ini /usr/local/etc/php/php.ini


COPY ./www.conf /usr/local/etc/php-fpm.d/www.conf


# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1

# Download and install pumukit to get the composer cache and improve build times
RUN git clone https://github.com/pumukit/pumukit /srv/pumukit; \
	cd /srv/pumukit; \
	git checkout 3.3.x; \
	composer install -a -n --no-scripts
RUN rm -rf /srv/pumukit

ENV PATH="${PATH}:/root/.composer/vendor/bin"

WORKDIR /srv/pumukit
