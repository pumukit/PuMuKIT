Docker PuMuKIT
==============

This directory holds `Dockerfiles` for creating PuMuKIT Docker images

Requirements
------------

You must have [`docker`](https://docs.docker.com/install/)  and [`docker-composer`](https://docs.docker.com/compose/install/) installed

On the other hand, you have to configure the docker service to use [`Overlay2`](https://docs.docker.com/storage/storagedriver/overlayfs-driver/#prerequisites) as a storage driver


Quick Start
-----------

You can clone this repository and launch a basic instance of PuMuKIT with the following command:

```sh
$ make up
```

You can access to the new local PuMuKIT docker instance with the following url: https://localhost


Build
-----

If you want to build the images yourself, use the following command :

```sh
$ make build
```
