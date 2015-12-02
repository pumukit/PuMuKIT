WorkflowBundle Configuration Guide
==================================

This bundle provides a service to automatically extract a picture
from a video that just has been transcoded if the MultimediaObject
does not have any picture yet.

By default, this service is enabled. To disabled it, add these lines
to the `app/config/parameters.yml` file of the Pumukit project:

```
pumukit_workflow:
    auto_extract_pic: false
```