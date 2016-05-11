# Opencast Bundle
This bundle is used to add [Opencast](http://www.opencast.org/) support to the PuMuKIT platform. With it, videos hosted in your Opencast server can be imported and published into your PuMuKIT Web TV Portal.

The OpencastBundle comes deactivated by default. In order to use it, it must be configured and installed.

1. Follow our [Configuration Guide](ConfigurationGuide.md) to learn how to configure this bundle adding the necessary parameters from your Opencast server.

2. Install the bundle by executing the following command.
```bash
php app/console pumukit:install:bundle Pumukit/OpencastBundle/PumukitOpencastBundle
```

3. If permission profiles have been created before installing the bundle with the `pumukit:init:repo` command, update it executing the following commands:
```bash
php app/console pumukit:permission:update Administrator ROLE_ACCESS_IMPORTER
php app/console pumukit:permission:update Publisher ROLE_ACCESS_IMPORTER
php app/console pumukit:permission:update Ingestor ROLE_ACCESS_IMPORTER
```

