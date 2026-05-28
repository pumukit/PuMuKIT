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
