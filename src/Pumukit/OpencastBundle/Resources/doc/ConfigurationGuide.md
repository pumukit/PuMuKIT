# Opencast configuration

This is the Opencast Bundle Configuration Guide. Check our [README](README.md) to learn more about this bundle.

## Index

1. [Parameters](#1-parameters)
2. [Cron tool](#2-cron-tool)

## 1. Parameters

Add your Opencast server configuration to your `app/config/parameters.yml` file:

```
pumukit_opencast:
    host: 'http://demo.opencast.org:8080'
    username: 'matterhorn_system_account'
    password: 'CHANGE_ME'
    player: /engage/ui/watch.html
    use_redirect: true
    batchimport_inverted: false
    show_importer_tab: true
    delete_archive_mediapackage: false
    deletion_workflow_name: 'delete-archive'
    sbs:
        generate_sbs: true
        profile: sbs
        use_flavour: true
        flavour: composition/delivery
    error_if_file_not_exist: true
    url_mapping:
        -
          url: 'http://demo.opencast.org/static/engage-player/'
          path: /srv/matterhorn/downloads/engage-player/
        -
          url: 'http://engage-demo.opencast.org/static/engage-player/'
          path: /mnt/path/to/share/srv/matterhorn/downloads/engage-player/
        ...
```
Mandatory:
   - `host` is the Opencast Matterhorn server URL (Engage node in cluster).
   - `username` is the name of the account used to operate the Matterhron REST endpoints (org.opencastproject.security.digest.user). If empty, the connection is as an anonymous user.
   - `password` is the password for the account used to operate the Matterhorn REST endpoints (org.opencastproject.security.digest.pass).
   - `player` is the Opencast player URL or path (default `/engage/ui/watch.html`). Use `/engage/theodul/ui/core.html` for Opencast 2.x and `/paella/ui/watch.html` if [paella player](http://paellaplayer.upv.es/) is being used.

Optional:
   - `default_tag_imported` set code of tag that you want to set when imports an mmo to PuMuKIT2 from Opencast.
   - `use_redirect` when set to false, an Opencast video will be displayed inside an iframe into PuMuKIT. Default value: true, the Opencast video is displayed on the Opencast server.
   - `batchimport_inverted` when set to true, the Opencast videos will be imported with presentation and presented inverted, i.e. switching positions. Default value: false.
   - `show_importer_tab` when set to false, the Opencast Importer Tab will not be shown. Useful when the importation is done using batch import command. Default value: true.
   - `delete_archive_mediapackage` when set to true, the Opencast mediapackage will be deleted from the archive when deleting the PuMuKIT track or multimedia object. Default value: false.
   - `deletion_workflow_name` is the name of the Opencast workflow in Opencast that handles the deletion of a mediapackage from the archive. Default value: delete-archive.
   - `sbs`:
      - `generate_sbs` when set to true, generates side by side video when MP is imported according to the profile below. Default value: false.
      - `profile` is the profile name to generate the side by side video. Mandatory if `generate_sbs` is set to true.
      - `use_flavour` when set to true, it will use a Track with given below flavour as side by side track. Default value: false.
      - `flavour` is the name of the flavour of an Opencast track to be used as side by side track in PuMuKIT. Default value: composition/delivery.
   - `error_if_file_not_exist` throw an error if the track file doesn't exist or it is not accessible using the url mapping info. Default value: true.
   - `url_mappging` is a list of url-path mappging used to generate the side by side video. Mandatory if `generate_sbs` is set to true.
      - `url` is the internal URL of the Opencast Matterhorn installation, used by Matterhorn to locate services running on the instance and for inter-node communication in distributed setups involving more than one Matterhorn node (org.opencastproject.server.url).
      - `path` is the directory where the system will store its processed files (including temporary files). This directory should be persistent between reboots (i.e., not /tmp) (org.opencastproject.storage.dir).

For more info about Opencast Matterhorn parameters go to [Opencast Documentation](https://bitbucket.org/opencast-community/matterhorn/src/d9890525acc0c14ee20b2523da4873551c6a91f2/etc/config.properties?at=master)


## 2. Cron tool

List of PuMuKIT commands that must be configured with the cron tool.

### 2.1. Batch Import

The `pumukit:opencast:batchimport` console command allows to import all Opencast videos into PuMuKIT at once.
This command allows to import all videos with or without invert option to switch camera and screen positions.
This option can be given with the command line option `--invert` with values `1` as true and `0` as false,
or just use the `batchimport_inverted` parameter value.

The recommendation for its use is to configure the cron tool on the PuMuKIT system, to execute this command periodically.
All videos already imported will be skipped, adding the new Opencast recordings to PuMuKIT.

Configure cron to synchronize PuMuKIT with Opencast. To do that, you need to add one of the following commands to the crontab file,
whether you want to import all the videos imported or not. The first command will use the value of the `batchimport_inverted` parameter.

```
sudo crontab -e
```

The recommendation on a development environment is to run commands every minute.
The recommendation on a production environment is to run commands every day, e.g.: every day at time 23:40.

```
40 23 * * *     /usr/bin/php /var/www/pumukit2/app/console pumukit:opencast:batchimport --env=prod
40 23 * * *     /usr/bin/php /var/www/pumukit2/app/console pumukit:opencast:batchimport --env=prod -i 1
40 23 * * *     /usr/bin/php /var/www/pumukit2/app/console pumukit:opencast:batchimport --env=prod -i 0
```

### 2.2. Workflow Stop

The `pumukit:opencast:workflow:stop` console command allows to stop all Opencast succeeded workflows of
a removed media package.

If the `delete_archive_mediapackage` parameter is set to true, there is no need to add this command to
the crontab configuration.

Configure cron to execute this command periodically.

```
sudo crontab -e
```

The recommendation on a development environment is to run commands every minute.
The recommendation on a production environment is to run commands every day, e.g.: every day at time 23:40.

```
40 23 * * *     /usr/bin/php /var/www/pumukit2/app/console pumukit:opencast:workflow:stop --env=prod
```
