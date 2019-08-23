BasePlayerBundle configuration
==============================

Configuration:

```
pumukit_base_player:
    secure_secret: null
    secure_duration: 3600
    when_dispatch_view_event: on_load
```

* `secure_secret` Defines a secret word used to generate authenticated requested links for the ngx_http_secure_link_module. NULL to disable.
* `secure_duration` The lifetime of a link passed in a request when secure_secret is defined. Default one hour (3600s)
* `when_dispatch_view_event` When dispatch a view event, on load the track file or on play the video (via AJAX request). Values accepted: [on_load, on_play].
