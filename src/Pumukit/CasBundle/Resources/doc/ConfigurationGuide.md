CAS configuration
=================

To configure CAS update your `app/config/security.yml` file:

```
security:
    firewalls:
        main:
            pattern:      ^/
            pumukit:      true
            logout:
                path: /logout
                success_handler: pumukit.security.handler.logout
            anonymous:    true
```


And `app/config/parameters.yml` file with:


```
pumukit_security:
    cas_url: 'login.XXXXXX.es'
    cas_port: 443
    cas_uri: 'cas'
```


Note: Single Sign Out only works with native PHP session save handlers.
