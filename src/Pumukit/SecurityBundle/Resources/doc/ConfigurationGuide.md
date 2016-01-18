Security configuration
======================

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
