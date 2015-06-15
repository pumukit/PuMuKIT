PumukitNotificationBundle AdminGuide
====================================

Description
-----------

PumukitNotificationBundle sends emails when a job finished, whether it failed or succeed.

How to configure NotificationBundle
-----------------------------------

1.- Enable NotificationBundle by uncommenting the following line in the `app/AppKernel.php` file of your project:

```
php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new Pumukit\NotificationBundle\PumukitNotificationBundle(),
        );

        // ...
    }

    // ...
}
```

2.- Add these parameters to your `app/config/config.yml` file:

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