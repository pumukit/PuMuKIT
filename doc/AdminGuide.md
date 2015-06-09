PuMuKIT-2 SysAdmin Guide
========================

How to activate the cache in a production environment
-----------------------------------------------------

1. Uncomment this lines in your `web/app.php` file:

    ```
    require_once __DIR__.'/../app/AppCache.php';

    $kernel = new AppCache($kernel);

    Request::enableHttpMethodParameterOverride();
    ```

2. Clear the cache:

    ```
    $ php app/console cache:clear --env=prod --no-debug
    ```
