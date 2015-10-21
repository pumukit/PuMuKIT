PuMuKIT-2 Developer & Architecture Guide
========================================

Architecture
============

Technologies
------------

* Symfony 2.6
* PHP 5.6
* MongoDB 3.0
* Bootstrap
* Material Design
* FFMPEG or libav


List of Bundles
---------------

Activated by default:
* ArcaBundle
* EncoderBundle
* ExampleDataBundle
* InspectionBundle
* InstallBundleBundle
* LiveBundle
* NewAdminBundle
* [OpencastBundle](../src/Pumukit/OpencastBundle/Resources/doc/ConfigurationGuide.md)
* SchemaBundle
* StatsBundle
* WebTVBundle
* WizardBundle
* [WorkflowBundle](../src/Pumukit/WorkflowBundle/Resources/doc/ConfigurationGuide.md): This bundle provides a service to automatically extract a picture from a video that just has been transcoded if the MultimediaObject does not have any picture yet.


Not activated by default:
* [LDAPBundle](../src/Pumukit/LDAPBundle/Resources/doc/AdminGuide.md)
* [MoodleBundle](../src/Pumukit/NotificationBundle/Resources/doc/InstallationGuide.md)
* [NotificationBundle](../src/Pumukit/NotificationBundle/Resources/doc/AdminGuide.md): sends emails when a job finished, whether it failed or succeed
* [PodcastBundle](../src/Pumukit/PodcastBundle/Resources/doc/InstallationGuide.md)


List of Events
--------------

Custom events added to PuMuKIT-2:
* *multimediaobject.update*: thrown each time a multimedia object is updated.
* *multimediaobject.view*: thrown each time a multimedia object is played in the webtv portal.
* *job.success*: thrown each time a job is finished successfully in the system.
* *job.error*: thrown each time a job fails in the system.

To add more events, read the Symfony documentation about creating [custom events](http://symfony.com/doc/current/components/event_dispatcher/introduction.html#creating-and-dispatching-an-event) and [listeners](http://symfony.com/doc/current/cookbook/event_dispatcher/event_listener.html).


How to extend Pumukit
=====================

Best practices:
* Do not modify PuMuKIT-2 Bundles, extend them following the Symfony documentation about [overriding Bundles](http://symfony.com/doc/current/cookbook/bundles/inheritance.html).
* Create your own Bundles.


Override WebTVBundle manual
---------------------------

Overriding the PumukitWebTVBundle allows you to change:

* Footer
* Header


Process
--------

### 1.- Create new WebTV bundle.

#### 1.1 Generate the bundle.

`
$ php app/console  generate:bundle --namespace=Pumukit/ExampleOrg/WebTVBundle --dir=src --no-interaction
`

#### 1.2 Register the new bundle as the "parent" of the Pumukit bundle:


```php
#src/Pumukit/ExampleOrg/WebTVBundle/PumukitExampleOrgWebTVBundle.php
<?php
namespace Pumukit\ExampleOrg\WebTVBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class PumukitExampleOrgWebTVBundle extends Bundle
{
    public function getParent()
    {
        return 'PumukitWebTVBundle';
    }
}
```

For more info see: http://symfony.com/doc/current/cookbook/bundles/inheritance.html

#### 1.3 Install the new bundle (if necessary).
`
$ php app/console  pumukit:install:bundle Pumukit/ExampleOrg/WebTVBundle/PumukitExampleOrgWebTVBundle
`

### 2.- Header

Add your HTML on `src/Pumukit/ExampleOrg/WebTVBundle/Resources/views/header.html.twig`.


### 3.- Change the footer
Add your HTML on `src/Pumukit/ExampleOrg/WebTVBundle/Resources/views/footer.html.twig`.


Examples
--------

* [Cmar/LiveBundle](../src/Pumukit/Cmar/LiveBundle/Resources/doc/AdminGuide.md) overrides LiveBundle.
* Cmar/SonarBundle is a new Bundle for Cmar organization.
* [Cmar/WebTVBundle](../src/Pumukit/Cmar/WebTVBundle/Resources/doc/AdminGuide.md) overrides WebTVBundle



Main URLs of a PuMuKIT deployment
---------------------------------
* Web Portal: `http://{MyPuMuKIT_IP}/`
* Back-office (admin interface): `http://{MyPuMuKIT_IP}/admin`
  * ARCA: `http://{MyPuMuKIT_IP}/arca.xml`
