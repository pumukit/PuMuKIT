# PuMuKIT-2 Installation Guide

*This page is updated to the 2.1 release*

## Requirements

PuMuKIT-2 is a LEMP (Linux, nginx, MongoDB, PHP) application, created with the Symfony2 framework. It uses libav-tools (or ffmpeg) to analyze the audiovisual data, as well as to transcode the data.

The requirements for installation are linux, nginx, libav-tools, php5 and mongo. Libav-tools with h264 and aac support is needed. Also the following php5 modules are required: php5-json, php5-cli, php5-mongo, php5-ldap, php5-curl and php5-intl. Make sure text search is enabled for your mongodb (version 2.6+).

Use [composer](https://getcomposer.org/) to check and install the dependencies

PuMuKIT-2 has been developed and is often installed on Linux Ubuntu but its use is not essential. It is known it works on Ubuntu 14.04. If it is installed on other Linux distributions, additional libraries may be required.

## Installation on Linux Ubuntu 14.04

Setup a development environment on Ubuntu 14.04. Go to [F.A.Q. section](#faq) if any error is thrown:

1. Update APT source list to install last version of MongoDB.

    ```
    sudo apt-key adv --keyserver hkp://keyserver.ubuntu.com:80 --recv 7F0CEB10
    echo "deb http://repo.mongodb.org/apt/ubuntu "$(lsb_release -sc)"/mongodb-org/3.0 multiverse" | sudo tee /etc/apt/sources.list.d/mongodb-org-3.0.list
    sudo apt-get update
    ```

2. Install dependencies of PuMuKIT-2 (see requirements):

    ```
    sudo apt-get install -y git curl nginx-full
    sudo apt-get install -y php5-fpm php5-cli php5-curl php5-intl php5-json
    sudo apt-get install -y php5-intl php5-xdebug php5-curl
    sudo apt-get install -y mongodb-org php5-mongo
    sudo apt-get install -y libav-tools libavcodec-extra
    ```

3. Create web directory and give the right permissions:

    ```
    sudo mkdir -p /var/www
    sudo adduser `whoami` www-data
    sudo chown `whoami`:www-data -R /var/www
    sudo chmod 0755 -R /var/www
    sudo chmod g+s -R /var/www
    ```

4. Download the last version of PuMuKIT-2:

    ```
    git clone https://github.com/campusdomar/PuMuKIT2.git /var/www/pumukit2
    ```

5. Activate the master branch:

    ```
    cd /var/www/pumukit2
    git checkout master
    ```

6. Install [composer](https://getcomposer.org/).

    ```
    curl -sS https://getcomposer.org/installer | php
    ```

7. Install dependencies and provide PuMuKIT-2 parameters:

    ```
    php composer.phar install
    ```

8. Give cache and log directories the right permissions.

   * Follow the instructions at Symfony [documentation](http://symfony.com/doc/current/book/installation.html#checking-symfony-application-configuration-and-setup).

9. Set the "date.timezone" setting in php.ini with your timezone (e.g. Europe/Madrid):

    ```
    sudo sed -i "s/;date.timezone =/date.timezone = Europe\/Madrid/g" /etc/php5/fpm/php.ini 
    ```

10. Set "xdebug.max_nesting_level" to "1000" in PHP configuration to stop Xdebug's infinite recursion protection erroneously throwing a fatal error:

    ```
    echo "xdebug.max_nesting_level=1000" | sudo tee -a /etc/php5/fpm/conf.d/20-xdebug.ini
    ```

11. Check environment requirements:

    * Go to `http://{PuMuKIT-2-HOST}/config.php` for checking requirements.
    * Fix errors if any and restart PHP5-FPM service. Fix warnings if necessary (PDO drivers are not necessary for PuMuKIT-2 to work).
      ```
      sudo service php5-fpm restart
      ```
    * Check requirements again
    * Repeat all steps until the MAJOR PROBLEMS list disappears.

12. Prepare environment (init mongo db, clear cache)

    ```
    php app/console doctrine:mongodb:schema:create
    php app/console cache:clear
    php app/console cache:clear --env=prod
    ```

13. Create the admin user

    ```
    php app/console fos:user:create admin --super-admin
    ```

14. Load default values (tags, broadcasts and roles).

    ```
    php app/console pumukit:init:repo all --force
    ```

15. [Optional] Load example data (series and multimedia objects)

    ```
    php app/console pumukit:init:example  --force    
    ```

16. Add NGINX config file.

    ```
    sudo cp doc/conf_files/nginx/default /etc/nginx/sites-available/default
    ```

17. Restart server

    ```
    sudo service php5-fpm restart
    sudo service nginx restart
    ```

18. Connect and enjoy

    * Connect to the frontend here: `http://{PuMuKIT-2-HOST}/`
    * Connect to the backend (Admin UI) with the user created on step 6 here: `http://{PuMuKIT-2-HOST}/admin`


## Installation of a development environment

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

## F.A.Q.

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


**Add GitHub token**

In case the `php composer.phar install` asks for a token with a  message like:

```
Could not fetch https://api.github.com/repos/VENDOR_NAME/NAME_Bundle/zipball/HASH_CODE, please create a GitHub OAuth token to go over the API rate limit
Head to https://github.com/settings/tokens/new?scopes=repo&description=Composer+on+HOST_ID+DATE+TIME
to retrieve a token. It will be stored in "/home/USER/.composer/auth.json" for future use by Composer.
Token (hidden):
```

There is a rate limit on GitHub's API for downloading repos. In case of reaching that limit, Composer prompts the above message asking for authentication to download a repo.

It is necessary to create a token and add it to composer.

1.- Create an OAuth token on GitHub. Follow the instructions on [GitHub Help page](https://help.github.com/articles/creating-an-access-token-for-command-line-use/).

2.- Add the token to the configuration running:

```
cd /var/www/pumukit2
php composer.phar config -g github-oauth.github.com <oauthtoken>
```

Now Composer should install/update without asking for authentication.


**Not allowed to access app_dev.php via web**

If you get this message when trying to access http://{PuMuKIT-2-HOST}/app_dev.php:
```
You are not allowed to access this file. Check app_dev.php for more information.
```

Comment the following code in `web/app_dev.php` file:

```php
/*
// This check prevents access to debug front controllers that are deployed by accident to production servers.
// Feel free to remove this, extend it, or make something more sophisticated.
if (isset($_SERVER['HTTP_CLIENT_IP']) || isset($_SERVER['HTTP_X_FORWARDED_FOR']) || !(in_array(@$_SERVER['REMOTE_ADDR'], array('127.0.0.1', 'fe80::1', '::1')) || php_sapi_name() === 'cli-server')
) {
header('HTTP/1.0 403 Forbidden');
exit('You are not allowed to access this file. Check '.basename(FILE).' for more information.');
}
*/
```


**Not allowed to access config.php via web**

If you get this message when trying to access http://{PuMuKIT-2-HOST}/config.php:
```
This script is only accessible from localhost.
```

Comment the following code in `web/config.php` file:

```php
/*
if (!in_array(@$_SERVER['REMOTE_ADDR'], array(
    '127.0.0.1',
    '::1',
))) {
    header('HTTP/1.0 403 Forbidden');
    exit('This script is only accessible from localhost.');
}
*/
```
