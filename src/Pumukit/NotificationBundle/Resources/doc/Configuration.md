PumukitNotificationBundle AdminGuide
====================================

Description
-----------

PumukitNotificationBundle sends emails when a job finished, whether it failed or succeed.

How to configure NotificationBundle
-----------------------------------

1.- Install the bundle into your Pumukit root project:

```
bash
$ cd /path/to/pumukit/
$ php app/console pumukit:install:bundle Pumukit/NotificationBundle/PumukitNotificationBundle
```

2.- Add these parameters to your `app/config/parameters.yml` file:

```
pumukit_notification:
    enable: true
    platform_name: 'Your Platform Name'
    sender_email: no-reply@yourplatform.com
    sender_name: 'Your Team Name'
    template: '@PumukitNotification/Email/job.html.twig'
    subject_success: 'Subject of email on job success'
    subject_fails: 'Subject of email on job fail'
    enable_multi_lang: true
    subject_success_trans:
        - {locale: en, subject: 'Job success'}
        - {locale: es, subject: 'Trabajo exitoso'}
    subject_fails_trans:
        - {locale: en, subject: 'Job fails'}
        - {locale: es, subject: 'Trabajo fallido'}
    admin_email:
        - admin1@yourplatform.com
        - admin2@yourplatform.com
    notificate_errors_to_admin: true
```

* `enable` defines whether activate the notifications through email or not.
* `template` defines the the template that you want use
* `subject_success` defines the subject of email when the job success
* `subject_fails` defines the subject of email when the job fails
* `enable_multi_lang` enables to send emails in multiple languages.
* `subject_success_trans` defines the `subject_success` in multiple languages in case you want to send multi language email.
* `subject_fails_trans` defines the `subject_fails` in multiple languages in case you want to send multi language email.
* `notificate_errors_to_admin` defines whether send an email to admin(s) when a job finished with errors.
