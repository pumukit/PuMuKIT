Installation Guide
==================

Steps to install and configure this bundle:

1.- Install the bundle into your Pumukit2 root project:

```bash
$ cd /path/to/pumukit2/
$ php app/console pumukit:install:bundle Pumukit/ArcaBundle/PumukitArcaBundle
```

2.- Init ARCA tags:

```
$ cd /path/to/pumukit2/
$ php app/console arca:init:tags --force
```