PuMuKIT-2 Installation Guide
====================================

*This page is updated to the 2.0 release* 

Requirements
-------------------------------------

PuMuKIT-2 is a LAMP application, created with the Symfony2 framework. It uses ffmpeg (or libav-tools) to analyze the audiovisual data, as well as to transcode them.

The requirements for installation are linux, ngonx, mongo, ffmpeg, php5. You must have installed a version of ffmpeg encoding to h264 and aac. Also the following modules are required php5: php5-ffmpeg, php5-cli, php5-mongo, php5-ldap, php5-curl and php5-intl.

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
    git clone http://gitlab.teltek.es/pumukit/pumukit2.git /var/www/pumukit2
    cd /var/www/pumukit2
    git checkout 2.0.0
    ```

3. Add NGINX config file.

    ```
    cp doc/conf_files/nginx/default /etc/nginx/sites-available/default
    ```

4. Install [composer](https://getcomposer.org/).

    ```
    curl -sS https://getcomposer.org/installer | php
    ```

5. Install dependencies

    ```
    php composer.phar install
    ```

6. Prepare environment (check requirements, init mongo db, clear cache)

    ```
    php app/check.php
    php app/console doctrine:mongodb:schema:create
    php app/console cache:clear
    ```

7. Create the admin user

    ```
    php app/console fos:user:create admin --super-admin
    ```

8. Restart server

    ```
    service nginx restart 
    ```


Installation a development environment
-------------------------------------

To quick develop you could use the PHP built-in web server.

```
# User develop
git checkout develop

# Cache clear
php app/console cache:clear

# Execute tests
php bin/phpunit -c app

# Start server
php app/console server:run
```

F.A.Q.
-------------------------------------

**Setting up Permissions?**

 * http://symfony.com/doc/current/book/installation.html#checking-symfony-application-configuration-and-setup
 * http://symfony.es/documentacion/como-solucionar-el-problema-de-los-permisos-de-symfony2/

**403 Forbidden access to config.php and app_dev.php**

 * Uncomment code in `web/config.php` and `web/app_dev.php`