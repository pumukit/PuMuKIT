PumukitNotificationBundle AdminGuide
====================================

Description
-----------

PumukitNotificationBundle sends emails when a job finished, whether it failed or succeed.

How to configure NotificationBundle
-----------------------------------

1.- Install the bundle into your Pumukit2 root project:

```
bash
$ cd /path/to/pumukit2/
$ php app/console pumukit:install:bundle Pumukit/NotificationBundle/PumukitNotificationBundle
```

2.- Add these parameters to your `app/config/parameters.yml` file:

```
pumukit_notification:
    enable: true
    platform_name: 'Your Platform Name'
    sender_email: no-reply@yourplatform.com
    sender_name: 'Your Team Name'
    admin_email:
        - admin1@yourplatform.com
        - admin2@yourplatform.com
    notificate_errors_to_admin: true
```

* `enable` defines whether activate the notifications through email or not.
* `notificate_errors_to_admin` defines whether send an email to admin(s) when a job finished with errors.