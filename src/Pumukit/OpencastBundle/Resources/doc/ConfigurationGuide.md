Opencast configuration
======================

Add your Opencast server configuration to your `app/config/parameters.yml` files:

```
    opencast_host: ''
    opencast_username: ''
    opencast_password: ''
    opencast_player: ''
```

   - `opencast_host` is the Opencast server URL (Engage node in cluster).
   - `opencast_username` is the name of the account used to operate the Matterhron REST endpoints (org.opencastproject.security.digest.user).
   - `opencast_password` is the password for the account used to operate the Matterhorn REST endpoints (org.opencastproject.security.digest.pass).
   - `opencast_player` is the Opencast player URL or path (default /engage/ui/watch.html).