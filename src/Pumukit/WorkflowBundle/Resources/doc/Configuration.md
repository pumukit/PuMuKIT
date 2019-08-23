WorkflowBundle Configuration
============================

This bundle provides a service to automatically extract a picture from a video that just has been transcoded if the MultimediaObject does not have any picture yet.

Configuration:

```
pumukit_workflow:
    auto_extract_pic: false
    auto_extract_pic_percentage: 50%
    dynamic_pic_extract: true
    dynamic_pic_extract_track_tag_allowed: master
```

* `auto_extract_pic` Extract thumbnail automatically
* `auto_extract_pic_percentage` Extract thumbnail automatically on this percentage
* `dynamic_pic_extract` Extract dynamic pic thumbnail automatically
* `dynamic_pic_extract_track_tag_allowed` Extract thumbnail automatically on this percentage
