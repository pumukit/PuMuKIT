PumukitLDAPBundle AdminGuide
============================

Description
-----------

The PumukitLDAPBundle allows to connect to a LDAP Server
and to retrieve data from the server for easy metadata filling.

**Note:** Authentication and authorization features are not included in this module yet. These features are under development and will be available soon.

This bundle is disabled by default. To install it and
configure it, read the below section.


How to install and configure LDAPBundle
---------------------------------------

1.- Install the bundle into your Pumukit root project:

```
$ cd /path/to/pumukit/
$ php app/console pumukit:install:bundle Pumukit/LDAPBundle/PumukitLDAPBundle
```

2.- Add and configure these parameters into your `app/config/parameters.yml` file
with your LDAP server parameters:

```
pumukit_ldap:
    server: 'ldap://localhost'
    bind_rdn: 'cn=readonly,ou=teachers,dc=campusdomar,dc=es'
    bind_password: 'readonly'
    base_dn: 'ou=teachers,dc=campusdomar,dc=es'
```

* `server` defines the DNS address of the LDAP Server.
* `bind_rdn` defines the DN of the search engine. If not specified, anonymous bind is attempted.
* `bind_password` defines the password of the search engine. If not specified, anonymous bind is attempted.
* `base_dn` defines an user DN of the LDAP Server.
