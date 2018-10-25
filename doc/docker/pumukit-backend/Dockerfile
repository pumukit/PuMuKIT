ARG PHP_VERSION=7.2
ARG NGINX_VERSION=1.15


FROM php:${PHP_VERSION}-fpm-alpine AS pumukit_backend
MAINTAINER Pablo Nieto, pnieto@teltek.es


RUN apk add --no-cache \
		acl \
		file \
		gettext \
		git \
;

ARG APCU_VERSION=5.1.12
RUN set -eux; \
 	apk add --no-cache --virtual .build-deps \
 		$PHPIZE_DEPS \
 		icu-dev \
# 		libzip-dev \
# 		zlib-dev \
 	; \
 	\
# 	docker-php-ext-configure zip --with-libzip; \
 	docker-php-ext-install -j$(nproc) \
 		intl \
		exif \
# 		zip \
 	; \
 	pecl install \
 		apcu-${APCU_VERSION} \
 	; \
 	pecl clear-cache; \
 	docker-php-ext-enable \
 		apcu \
 		opcache \
 	;




# RUN apt-get update && \
#     apt-get -y install software-properties-common


# #Install dependencies of PuMuKIT-2
# #RUN apt-get install -y git curl php5-fpm php5-cli php5-curl php5-intl php5-json \
# #    php5-intl php5-xdebug php5-curl php5-ldap mongodb-org php5-mongo libav-tools libavcodec-extra

# RUN ls /usr/local/etc/php

# # Set default php configuracion
# RUN echo -e "\ndate.timezone = Europe/Madrid" >> /etc/php5/cli/php.ini && \
#     echo -e "\ndate.timezone = Europe/Madrid" >> /etc/php5/fpm/php.ini && \
#     sed -i 's/memory_limit = .*/memory_limit = 512M/' /etc/php5/cli/php.ini && \
#     sed -i 's/memory_limit = .*/memory_limit = 512M/' /etc/php5/fpm/php.ini && \
#     sed -i "/post_max_size =/c\post_max_size = 2000M" /etc/php5/cli/php.ini && \
#     sed -i "/upload_max_filesize =/c\upload_max_filesize = 2000M" /etc/php5/cli/php.ini && \
#     sed -i "/post_max_size =/c\post_max_size = 2000M" /etc/php5/fpm/php.ini && \
#     sed -i "/upload_max_filesize =/c\upload_max_filesize = 2000M" /etc/php5/fpm/php.ini

# # Install composer
# RUN curl -sS https://getcomposer.org/installer | php && \
#     chmod +x composer.phar && \
#     mv composer.phar /usr/bin/composer

# #Install pumukit2 dependencies
# RUN git clone https://github.com/campusdomar/PuMuKIT2.git /var/www/pumukit2 && \
#     cd /var/www/pumukit2 && \
#     git checkout $PUMUKIT_VERSION && \
#     composer install -a -n --no-scripts
    
# ADD pumukit-launcher.sh /
# WORKDIR /var/www/pumukit2
