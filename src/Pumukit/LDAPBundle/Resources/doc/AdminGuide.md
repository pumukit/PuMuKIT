PumukitLDAPBundle AdminGuide
====================================

Description
-----------

PumukitLDAPBundle sends emails when a job finished, whether it failed or succeed.

How to configure LDAPBundle
-----------------------------------

1.- Install the bundle into your Pumukit2 root project:

```
$ cd /path/to/pumukit2/
$ php app/console pumukit:install:bundle Pumukit/LDAPBundle/PumukitLDAPBundle
```

2.- Add and configure these parameters into your `app/config/parameter.yml` file
with your LDAP server parameters:

```
pumukit_ldap:
    server: 'ldap://localhost'
    dn_search_engine: 'cn=readonly,ou=teachers,dc=campusdomar,dc=es'
    pass_search_engine: 'readonly'
    dn_user: 'ou=teachers,dc=campusdomar,dc=es'
```

* `server` defines the DNS address of the LDAP Server.
* `dn_search_engine` defines the DN of the search engine.
* `pass_search_engine` defines the password of the search engine.
* `dn_user` defines an user DN of the LDAP Server.