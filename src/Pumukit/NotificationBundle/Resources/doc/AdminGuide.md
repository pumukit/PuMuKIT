PumukitNotificationBundle AdminGuide
====================================

Description
-----------

PumukitNotificationBundle sends emails when a job finished, whether it failed or succeed.

How to configure NotificationBundle
-----------------------------------

1. Add these parameters to your `app/config/config.yml` file:

```
pumukit_notification:
    enable: true
    platform_name: 'Your Platform Name'
    sender_email: admin@yourplatform.com
    sender_name: 'Your Team Name'
    notificate_errors_to_sender: true
```

* `enable` defines whether activate the notifications through email or not.
* `notificate_errors_to_sender` defines whether send an email to sender when a job finished with errors.