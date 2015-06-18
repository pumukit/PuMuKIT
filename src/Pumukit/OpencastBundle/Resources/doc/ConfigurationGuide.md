Opencast configuration
======================

Add your Opencast server configuration to your `app/config/parameters.yml` files during installation:

```
    opencast_host: ''
    opencast_username: ''
    opencast_password: ''
    opencast_player: ''
```

   - `opencast_host` is the Opencast Matterhorn server URL (Engage node in cluster).
   - `opencast_username` is the name of the account used to operate the Matterhron REST endpoints (org.opencastproject.security.digest.user).
   - `opencast_password` is the password for the account used to operate the Matterhorn REST endpoints (org.opencastproject.security.digest.pass).
   - `opencast_player` is the Opencast player URL or path (default /engage/ui/watch.html).


Add optional Opencast configuration to your `app/config/config.yml` file:

```
pumukit_opencast:
    host: "%opencast_host%"
    username: "%opencast_username%"
    password: "%opencast_password%"
    player: "%opencast_player%"
    generate_sbs: false
    profile: sbs
    url_mapping:
        - {url: '', path: }
        - {url: '', path: }
        ...
```

   - `generate_sbs` when set to true, generates side by side video when MP is imported.
   - `profile` is the profile name to generate the side by side video.
   - `url_mappging` is a list of url-path mappging.
   - `url` is the internal URL of the Opencast Matterhorn installation, used by Matterhorn to locate services running on the instance and for inter-node communication in distributed setups involving more than one Matterhorn node (org.opencastproject.server.url).
   - `path` is the directory where the system will store its processed files (including temporary files). This directory should be persistent between reboots (i.e., not /tmp) (org.opencastproject.storage.dir).

For more info about Opencast Matterhorn parameters go to [Opencast Documentation](https://bitbucket.org/opencast-community/matterhorn/src/d9890525acc0c14ee20b2523da4873551c6a91f2/etc/config.properties?at=master)