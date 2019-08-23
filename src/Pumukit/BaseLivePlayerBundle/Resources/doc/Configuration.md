BaseLivePlayerBundle configuration
==================================

BaseLivePlayerBundle borns from the previous bundle LiveBundle. 

This change provides unique place to define all parameters for Events.

Configuration:

```
pumukit_base_live_player:
    advance_live_event: true
    event_default_poster: /bundles/pumukitwebtv/images/live_screen.jpg
    advance_live_event_create_default_pic: /bundles/pumukitwebtv/images/live/live_event_default_pic.jpg
    advance_live_event_create_serie_pic: /bundles/pumukitwebtv/images/live/live_event_series_pic.png
    advance_live_event_autocomplete_series: false
    liveevent_contact_and_share: false
    chat:
        enable: false
        update_interval: 5000
    twitter:
        enable: false
        accounts_link_color: #3b94d9
```

* `advance_live_event` Activate/Deactivate advanced lives events 
* `event_default_poster` Default poster for Advanced Live Events
* `advance_live_event_create_default_pic` Advance live event session default create from serie pic
* `advance_live_event_create_serie_pic` Advance live event session default create serie _pic
* `advance_live_event_autocomplete_series` Advance live event button to autocomplete series with event data
* `liveevent_contact_and_share` Shows the advance live event contact form
* `chat` `enable` Enable chat in live channel
* `chat` `update_interval` Interval in milliseconds to refresh the content of the chat.
* `twitter` `enable` Enable Twitter in live channel
* `twitter` `accounts_link_color` The text color of the accounts links in tweets when hovering. Default value: Twitter default text color #3b94d9
