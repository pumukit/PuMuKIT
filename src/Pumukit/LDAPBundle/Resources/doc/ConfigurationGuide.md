Security configuration
======================

To configure LDAP update your `app/config/security.yml` file:

```
security:
    providers:
        my_ldap:
            ldap:
                service: ldap
                base_dn: ou=people,dc=example,dc=es
                search_dn: "uid=uid_example,ou=sistema,dc=example,dc=es"
                search_password: xxxxx
                default_roles: ROLE_USER
                uid_key: uid

        ...
        
    firewalls:
        main:
            pattern: ^/
            context: pumukit
            form_login_ldap:
                login_path: /login
                check_path: /login_check
                service: ldap
                dn_string: 'uid={username},ou=people,dc=example,dc=es'
            logout:       true
            anonymous:    true
        ...
    ...
    
```

If you want to use just LDAP login add this parameter to the main firewall under form_login_ldap:

```
    firewalls:
        main:
            ...
            form_login_ldap:
                success_handler: pumukit_ldap.handler

```

Update your `app/config/parameters.yml` file with:


```
pumukit_ldap:
    server: 'your LDAP server'
    bind_rdn: 'uid=example,ou=sistema,dc=example,dc=es'
    bind_password: xxxxxx
    base_dn: 'ou=people,dc=example,dc=es'
```

Update your `app/config/services.yml` file with:

```
services:
    ldap:
        class: Symfony\Component\Ldap\LdapClient
        arguments:
            - host.com   # host
            - 389         # port
            - 3           # version
            - false       # SSL (true or false)
            - false        # TLS (true or false) 
```
