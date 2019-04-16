Installation Guide
==================

Steps to install and configure this bundle:

1.- Install the bundle into your Pumukit root project:

```bash
$ cd /path/to/pumukit/
$ php app/console pumukit:install:bundle Pumukit/PodcastBundle/PumukitPodcastBundle
```

2.- [OPTIONAL] Configure the parameters in your `app/config/parameters.yml` file:

```
pumukit_podcast:
    channel_title: 'PuMuKIT Channel'
    channel_description: 'PuMuKIT description of the channel'
    channel_copyright: 'PuMuKIT Team 2015'
    itunes_category: 'Education'
    itunes_summary: 'Education channel'
    itunes_subtitle: 'Itunes subtitle'
    itunes_author: 'PuMuKIT Team'
    itunes_explicit: false
```

* `channel_title` defines the title of the channel. If not defined, it will take pumukit.info values or Series values.
* `channel_description` defines the description of the channel. If not defined, it will take pumukit.info values or Series values.
* `channel_copyright` defines the copyright of the channel. If not defined, it will take pumukit.info values.
* `itunes_category` defines the Itunes category. Default value: 'Education'. This value must be in English: https://validator.w3.org/feed/docs/error/InvalidItunesCategory.html
* `itunes_summary` defines the Itunes summary. If not defined, it will take pumukit.info values.
* `itunes_subtitle` defines the Itunes subtitle. If not defined, it will take pumukit.info values.
* `itunes_author` defines the Itunes author of the channel. Default value: 'PuMuKIT-TV'.
* `itunes_explicit` defines whether Itunes is explicit or not. Default value: false.

3.- Init Podcast tags:

```
$ cd /path/to/pumukit/
$ php app/console podcast:init:tags --force
```

4.- [OPTIONAL] Init ItunesU tags:

```
$ cd /path/to/pumukit/
$ php app/console podcast:init:itunesu --force
```