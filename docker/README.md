Docker PuMuKIT
==============

This directory holds `Dockerfiles` for creating PuMuKIT Docker images

Requirements
------------

You must have [`docker`](https://docs.docker.com/install/) and [`docker compose`](https://docs.docker.com/compose/install/) installed.

It is also recommended to configure the docker daemon to use [`Overlay2`](https://docs.docker.com/storage/storagedriver/overlayfs-driver/#prerequisites) as the storage driver.


Quick Start
-----------

From the project root, launch a basic instance of PuMuKIT with:

```sh
make up
```

You can then access the local PuMuKIT instance at: https://localhost

The certificate is self-signed: accept it once at https://localhost and at https://dls.localhost, the download server that delivers the
videos. Until you accept the second one, the videos do not play and the browser shows no error in the page. Browsers resolve
`dls.localhost` by themselves; if yours does not, add `127.0.0.1 dls.localhost` to `/etc/hosts`.

Useful commands (see `make help` for the full list):

```sh
make ps      # service state
make logs    # follow service logs
make stop    # stop the containers
```


Build
-----

If you want to build the images yourself, use the following command:

```sh
make build
```
