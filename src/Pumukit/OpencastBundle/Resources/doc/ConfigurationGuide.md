Opencast configuration
======================

Add your Opencast server configuration to your `app/config/parameters.yml` file:

```
pumukit_opencast:
    host: 'http://demo.opencast.org:8080'
    username: 'matterhorn_system_account'
    password: 'CHANGE_ME'
    player: /engage/ui/watch.html
    use_redirect: true
    batchimport_inverted: false
    show_ingestor_tab: true
    sbs:
        generate_sbs: true
        profile: sbs
        use_flavour: true
        flavour: composition/delivery
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
   - `use_redirect` when set to false, an Opencast video will be displayed inside an iframe into PuMuKIT. Default value: true, the Opencast video is displayed on the Opencast server.
   - `batchimport_inverted` when set to true, the Opencast videos will be imported with presentation and presented inverted, i.e. switching positions.
   - `show_ingestor_tab` when set to false, the Opencast Ingestor Tab will not be shown. Useful when the ingestion is done using batch import command.
   - `sbs`:
      - `generate_sbs` when set to true, generates side by side video when MP is imported according to the profile below.
      - `profile` is the profile name to generate the side by side video. Mandatory if `generate_sbs` is set to true.
      - `use_flavour` when set to true, it will use a Track with given below flavour as side by side track.
      - `flavour` is the name of the flavour of an Opencast track to be used as side by side track in PuMuKIT.
      - `url_mappging` is a list of url-path mappging used to generate the side by side video. Mandatory if `generate_sbs` is set to true.
          - `url` is the internal URL of the Opencast Matterhorn installation, used by Matterhorn to locate services running on the instance and for inter-node communication in distributed setups involving more than one Matterhorn node (org.opencastproject.server.url).
          - `path` is the directory where the system will store its processed files (including temporary files). This directory should be persistent between reboots (i.e., not /tmp) (org.opencastproject.storage.dir).

For more info about Opencast Matterhorn parameters go to [Opencast Documentation](https://bitbucket.org/opencast-community/matterhorn/src/d9890525acc0c14ee20b2523da4873551c6a91f2/etc/config.properties?at=master)
