PuMuKIT-2 Installation Guide
====================================

*This page is updated to the 2.1 release* 

Requirements
-------------------------------------

PuMuKIT-2 is a LAMP application, created with the Symfony2 framework. It uses libav-tools (or ffmpeg) to analyze the audiovisual data, as well as to transcode them.

The requirements for installation are linux, nginx, libav-tools, php5 and mongo. Libav-tools with h264 and aac support is needed. Also the following php5 modules are required: php5-json, php5-cli, php5-mongo, php5-ldap, php5-curl and php5-intl. Make sure text search is enabled for your mongodb (version 2.6+).

Use [composer](https://getcomposer.org/) to check and install the dependencies

PuMuKIT-2 has been developed and is often installed on Linux Ubuntu but its use is not essential.

Installation
-------------------------------------

Setup a development environment on Ubuntu 14.04:

1. Update APT source list to install last version of MongoDB.

    ```
    sudo apt-key adv --keyserver hkp://keyserver.ubuntu.com:80 --recv 7F0CEB10
    echo "deb http://repo.mongodb.org/apt/ubuntu "$(lsb_release -sc)"/mongodb-org/3.0 multiverse" | sudo tee /etc/apt/sources.list.d/mongodb-org-3.0.list
    sudo apt-get update
    ```


2. Install dependencies of PuMuKIT-2 (see requirements):

    ```
    sudo apt-get install git curl nginx-full
    sudo apt-get install php5-fpm php5-cli php5-curl php5-intl php5-json
    sudo apt-get install php5-intl php5-xdebug php5-curl
    sudo apt-get install mongodb-org php5-mongo
    sudo apt-get install libav-tools libavcodec-extra
    ```

3. Download the last version of PuMuKIT-2:

    ```
    git clone https://github.com/campusdomar/PuMuKIT2.git /var/www/pumukit2
    cd /var/www/pumukit2
    git checkout master
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
    
8. Load default values (tags, broadcasts and roles).

    ```
    php app/console pumukit:init:repo all --force
    ```

9. [Optional] Load example data (series and multimedia objects)

    ```
    php app/console pumukit:init:example  --force    
    ```
    
10. Add NGINX config file.

    ```
    sudo cp doc/conf_files/nginx/default /etc/nginx/sites-available/default
    ```

11. Restart server

    ```
    sudo service php5-fpm restart
    sudo service nginx restart 
    ```

12. Connect and enjoy

    * Connect to the frontend here: `http://{MyPuMuKIT_IP}/`
    * Connect to the backend (Admin UI) with the user created on step 6 here: `http://{MyPuMuKIT_IP}/admin`


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

* /etc/php5/fpm/pool.d/www.conf

```
request_terminate_timeout = 30
```

* /etc/php5/fpm/php.ini

```
max_execution_time = 30
```

* Restart php5-fpm and NGINX:

```
sudo service php5-fpm restart
sudo service ningx restart
```


**Setting up Permissions?**

 * http://symfony.com/doc/current/book/installation.html#checking-symfony-application-configuration-and-setup
 * http://symfony.es/documentacion/como-solucionar-el-problema-de-los-permisos-de-symfony2/
 * Setting up ownership of upload directories

    ```
    sudo chown -R www-data:www-data web/storage/ web/uploads/
    ```


**Enable MongoDB text index**

Use MongoDB 2.6 or upper, the text search feature is enabled by default. In Ubuntu 14.04 you can install the last version of MongoDB using the next documentation:

 * http://docs.mongodb.org/manual/tutorial/install-mongodb-on-ubuntu/


**403 Forbidden access to config.php and app_dev.php**

 * Uncomment code in `web/config.php` and `web/app_dev.php`
