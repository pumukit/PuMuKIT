PuMuKIT-2 Installation Guide
====================================

*This page is updated to the 2.0 release* 

Requirements
-------------------------------------

PuMuKIT-2 is a LAMP application, created with the Symfony2 framework. It uses ffmpeg (or libav-tools) to analyze the audiovisual data, as well as to transcode them.

The requirements for installation are linux, nginx, mongo, ffmpeg, php5. You must have installed a version of ffmpeg encoding to h264 and aac. Also the following modules are required php5: php5-ffmpeg, php5-cli, php5-mongo, php5-ldap, php5-curl and php5-intl.

Use [composer](https://getcomposer.org/) to check and install the dependencies

PuMuKIT-2 has been developed and is often installed on Linux Ubuntu but its use is not essential.

Installation
-------------------------------------

Setup a development environment on Ubuntu 14.04:

1. Install dependencies of PuMuKIT-2 (see requirements):

    ```
    sudo apt-get install git php5-fpm php5-cli nginx-full
    sudo apt-get install php5-intl php5-xdebug php5-curl
    sudo apt-get install mongodb php5-mongo 
    sudo apt-get install mediainfo libav-tools
    ```

2. Download the last version of PuMuKIT-2:

    ```
    git clone https://github.com/campusdomar/PuMuKIT2.git /var/www/pumukit2
    cd /var/www/pumukit2
    git checkout master
    ```

3. Install [composer](https://getcomposer.org/).

    ```
    curl -sS https://getcomposer.org/installer | php
    ```

4. Install dependencies

    ```
    php composer.phar install
    ```

5. Prepare environment (check requirements, init mongo db, clear cache)

    ```
    php app/check.php
    php app/console doctrine:mongodb:schema:create
    php app/console cache:clear
    ```

6. Create the admin user

    ```
    php app/console fos:user:create admin --super-admin
    ```
    
7. Load default values (tags, broadcasts and roles).

    ```
    php app/console pumukit:init:repo all --force
    ```

8. [Optional] Load example data (series and multimedia objects)

    ```
    php app/console pumukit:init:example  --force    
    ```
    
9. Add NGINX config file.

    ```
    cp doc/conf_files/nginx/default /etc/nginx/sites-available/default
    ```

10. Restart server

    ```
    service nginx restart 
    ```


Installation a development environment
-------------------------------------

To quick develop you could use the PHP built-in web server.

```
# Use develop
git checkout develop

# Create new branch named develop if it is not created in local
git checkout -b develop

# Cache clear
php app/console cache:clear

# Execute tests
php bin/phpunit -c app

# Start server
php app/console server:run
```

F.A.Q.
-------------------------------------

**Configure max upload filesize**

1.- If you get a 413 response status code (request entity too large) and get "client intended to send too large body" in NGINX log. Check the NGINX conf file (/etc/nginx/sites-available/default)

```
client_max_body_size 2000m;
client_body_buffer_size 128k;
```

2.- If you get a message like "The file XXXX.avi exceeds your upload_max_filesize ini directive". Check the php-fpm conf file (/etc/php5/fpm/php.ini)

```
upload_max_filesize = 2000M
post_max_size = 2000M
```

3.- If you get a 504 "Gateway Time-out", change the php5-fpm configuration. Chose the same value for `request_terminate_timeout` in `/etc/php5/fpm/pool.d/www.conf` and `max_execution_time` in `/etc/php5/fmp/php.ini`files. For example:

/etc/php5/fpm/pool.d/www.conf

```
request_terminate_timeout = 30
```

/etc/php5/fpm/php.ini

```
max_execution_time = 30
```


**Setting up Permissions?**

 * http://symfony.com/doc/current/book/installation.html#checking-symfony-application-configuration-and-setup
 * http://symfony.es/documentacion/como-solucionar-el-problema-de-los-permisos-de-symfony2/
 * Setting up ownership of upload directories

    ```
    sudo chown -R www-data:www-data web/storage/ web/uploads/
    ```

**403 Forbidden access to config.php and app_dev.php**

 * Uncomment code in `web/config.php` and `web/app_dev.php`