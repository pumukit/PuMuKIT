FROM ubuntu:14.04
MAINTAINER Pablo Nieto, pnieto@teltek.es

ENV PUMUKIT_VERSION 2.3.0-RC3

# APT source list to install last version of MongoDB
RUN apt-get update && \
    apt-get -y install software-properties-common


RUN apt-key adv --keyserver hkp://keyserver.ubuntu.com:80 --recv 7F0CEB10 && \
    echo "deb http://repo.mongodb.org/apt/ubuntu "$(lsb_release -sc)"/mongodb-org/3.0 multiverse" | tee /etc/apt/sources.list.d/mongodb-org-3.0.list && \
    add-apt-repository ppa:mc3man/trusty-media && \
    apt-get update

#Install dependencies of PuMuKIT-2
RUN apt-get install -y git curl php5-fpm php5-cli php5-curl php5-intl php5-json \
    php5-intl php5-xdebug php5-curl php5-ldap mongodb-org php5-mongo libav-tools libavcodec-extra

# Set default php configuracion
RUN echo -e "\ndate.timezone = Europe/Madrid" >> /etc/php5/cli/php.ini && \
    echo -e "\ndate.timezone = Europe/Madrid" >> /etc/php5/fpm/php.ini && \
    sed -i 's/memory_limit = .*/memory_limit = 512M/' /etc/php5/cli/php.ini && \
    sed -i 's/memory_limit = .*/memory_limit = 512M/' /etc/php5/fpm/php.ini && \
    sed -i "/post_max_size =/c\post_max_size = 2000M" /etc/php5/cli/php.ini && \
    sed -i "/upload_max_filesize =/c\upload_max_filesize = 2000M" /etc/php5/cli/php.ini && \
    sed -i "/post_max_size =/c\post_max_size = 2000M" /etc/php5/fpm/php.ini && \
    sed -i "/upload_max_filesize =/c\upload_max_filesize = 2000M" /etc/php5/fpm/php.ini

# Install composer
RUN curl -sS https://getcomposer.org/installer | php && \
    chmod +x composer.phar && \
    mv composer.phar /usr/bin/composer

#Install pumukit2 dependencies
RUN git clone https://github.com/campusdomar/PuMuKIT2.git /var/www/pumukit2 && \
    cd /var/www/pumukit2 && \
    git checkout $PUMUKIT_VERSION
    
ADD pumukit-launcher.sh /
WORKDIR /var/www/pumukit2
