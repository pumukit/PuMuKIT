PuMuKIT Migration Guide
=======================

### Current version

PuMuKIT 3.8.*

### Next version

PuMuKIT 3.9.*

## Steps

1. Connect to backoffice as Administrator and go to Roles section

2. Create a new role with the next parameters:
- Code: former_owner
- XML : former_owner

3. Add new configuration variables

app/config/config.yml

```
parameters:
    pumukit.copyright_info_url: "%env(PUMUKIT_COPYRIGHT_INFO_URL)%"
    pumukit.license_info_url: "%env(PUMUKIT_LICENSE_INFO_URL)%"
```

dock/docker/.env

```
PUMUKIT_COPYRIGHT_INFO_URL=
PUMUKIT_LICENSE_INFO_URL=
```


