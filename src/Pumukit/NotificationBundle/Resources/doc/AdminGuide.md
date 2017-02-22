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
    sender_email: admin@yourplatform.com
    sender_name: 'Your Team Name'
    notificate_errors_to_sender: true
    template: 'PumukitNotificationBundle:Email:job.html.twig'
    subject_success: 'Subject of email on job success'
    subject_fails: 'Subject of email on job fail'
```

* `enable` defines whether activate the notifications through email or not.
* `notificate_errors_to_sender` defines whether send an email to sender when a job finished with errors.
* `template` defines the the template that you want use
* `subject_success` defines the subject of email when the job success
* `subject_fails` defines the subject of email when the job fails