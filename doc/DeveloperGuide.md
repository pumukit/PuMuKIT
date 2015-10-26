PuMuKIT-2 Developer & Architecture Guide
========================================

*This page is updated to the 2.1 release*

Architecture
============

Technologies
------------

PuMuKIT-2 has been built using the below technologies. It is recommended to read the documentation of each technology if you are interested on extending PuMuKIT-2 or create new features.

* Symfony 2: [web](http://symfony.com) | [doc](http://symfony.com/doc/current/index.html)
* PHP5: [web](http://php.net/) | [doc](http://php.net/manual/en/)
* MongoDB 3.0: [web](https://www.mongodb.org/) | [doc](https://docs.mongodb.org/v3.0/)
* Doctrine MongoDB ODM 1.0: [web](http://www.doctrine-project.org/projects/mongodb-odm.html) | [doc](http://doctrine-mongodb-odm.readthedocs.org/en/latest/)
* Bootstrap: [web](http://getbootstrap.com/) | [CSS doc](http://getbootstrap.com/css/) | [Components doc](http://getbootstrap.com/components/) | [JavaScript doc](http://getbootstrap.com/javascript/)
* Material Design [web](http://www.google.com/design/spec/material-design/introduction.html) | [doc](https://fezvrasta.github.io/bootstrap-material-design/)
* FFmpeg: [web](https://www.ffmpeg.org/) | [doc](https://www.ffmpeg.org/documentation.html)
* libav: [web](https://libav.org/) | [doc](https://libav.org/documentation/)


List of Bundles
---------------

PuMuKIT-2 has been developed with the Symfony 2 framework. Symfony 2 is based on bundles, and so, PuMuKIT-2 is structured in bundles too. There is a list of base-bundles that are activated by default in a standard PuMuKIT installation. There is also a list of optional base-bundles that will not be activated by default. In order to install/activate them, follow the instructions in each bundle guide.

You will find all bundles in `src/Pumukit/` of your project's root-directory.

Activated by default Bundles:
* ArcaBundle: provides a service to add RSS for the Academic Community.
* EncoderBundle: provides a service to encode audio/video tracks.
* ExampleDataBundle: provides a command to add example data on a new installation on the instance.
* InspectionBundle: provides a service to inspect multimedia tracks.
* InstallBundleBundle: provides a command to install new bundles into the PuMuKIT-2 project.
* LiveBundle: provides a service to show live stream events.
* NewAdminBundle: provides the back-office admin UI.
* [OpencastBundle](../src/Pumukit/OpencastBundle/Resources/doc/ConfigurationGuide.md): provides a service to import multimedia content of an Opencast Server.
* SchemaBundle: defines the schema of classes and services.
* StatsBundle: provides a service to log the statistics.
* [WebTVBundle](../src/Pumukit/WebTVBundle/Resources/doc/OverrideGuide.md): defines the portal web.
* WizardBundle: provides a service to guide the user on uploading multimedia content.
* [WorkflowBundle](../src/Pumukit/WorkflowBundle/Resources/doc/ConfigurationGuide.md): provides a service to automatically extract a picture from a video that just has been transcoded if the MultimediaObject does not have any picture yet.


Not activated by default Bundles:
* [LDAPBundle](../src/Pumukit/LDAPBundle/Resources/doc/AdminGuide.md): provides a service to connect to a LDAP Server and to retrieve data from the server.
* [MoodleBundle](../src/Pumukit/MoodleBundle/Resources/doc/InstallationGuide.md): allows to share PuMuKIT videos whitin a Moodle.
* [NotificationBundle](../src/Pumukit/NotificationBundle/Resources/doc/AdminGuide.md): sends emails when a job finished, whether it failed or succeed
* [PodcastBundle](../src/Pumukit/PodcastBundle/Resources/doc/InstallationGuide.md): provides a service to add PuMuKIT videos into Podcast channel.


List of Events
--------------

Symfony 2 works with events to listen to and to take actions when these events are dispatched. PuMuKIT-2 uses standard Symfony 2 events and these custom events specially added for the project:
* *multimediaobject.update*: thrown each time a multimedia object is updated.
* *multimediaobject.view*: thrown each time a multimedia object is played in the webtv portal.
* *job.success*: thrown each time a job is finished successfully in the system.
* *job.error*: thrown each time a job fails in the system.

If you need to add more events, read the Symfony documentation about creating [custom events](http://symfony.com/doc/current/components/event_dispatcher/introduction.html#creating-and-dispatching-an-event) and [listeners](http://symfony.com/doc/current/cookbook/event_dispatcher/event_listener.html).


How to extend and customize PuMuKIT
===================================

Best practices:
* Override a Bundle: If you need to customize PuMuKIT or add a minor feature you may need to modify one existent PuMuKIT bundle. DON'T DO IT !! Do not modify base PuMuKIT bundles directly, extend them following the Symfony documentation about [overriding Bundles](http://symfony.com/doc/current/cookbook/bundles/inheritance.html).
* Create a new Bundle: To add new big features you can create your own Bundles and add them to the PuMuKIT-2 project.
* As a good practice we recommend you to place all created and/or overridden bundles inside an "organization" directory in your PuMuKIT deployment:

```
Organization directory = src/Pumukit/MyUniversityName/

src/Pumukit/MyUniversityName/Feature1Bundle
src/Pumukit/MyUniversityName/Feature2Bundle
src/Pumukit/MyUniversityName/Feature3Bundle
...
```


Override a Bundle
-----------------

#### 1. Create the new override-bundle.

`
$ php app/console  generate:bundle --namespace=Pumukit/MyUniversityName/OverridenBundle --dir=src --no-interaction
`

#### 2. Register the new bundle as the "parent" of the original Pumukit bundle (overriden-bundle):


```php
#src/Pumukit/MyUniversityName/OverridenBundle/PumukitMyUniversityNameOverridenBundle.php
<?php
namespace Pumukit\MyUniversityName\OverridenBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class PumukitMyUniversityNameOverridenBundle extends Bundle
{
    public function getParent()
    {
        return 'PumukitOverridenBundle';
    }
}
```

For more info see: http://symfony.com/doc/current/cookbook/bundles/inheritance.html

#### 3. Install the new bundle (if necessary).
`
$ php app/console  pumukit:install:bundle Pumukit/MyUniversityName/OverridenBundle/PumukitMyUniversityNameOverridenBundle
`

#### Examples:

* [Cmar/LiveBundle](../src/Pumukit/Cmar/LiveBundle/Resources/doc/AdminGuide.md) It is a new custom-Bundle developed by the Cmar organization that overrides LiveBundle.
* [Cmar/WebTVBundle](../src/Pumukit/Cmar/WebTVBundle/Resources/doc/AdminGuide.md) It is a new custom-Bundle developed by the Cmar organization that overrides WebTVBundle


Create a new Bundle
----------------------

#### 1.1 Create the bundle scheleton.

`
$ php app/console  generate:bundle --namespace=Pumukit/MyUniversityName/FeatureBundle --dir=src --no-interaction
`

#### 1.2 Install the new bundle scheleton (if necessary).
`
$ php app/console  pumukit:install:bundle Pumukit/MyUniversityName/FeatureBundle/PumukitMyUniversityNameFeatureBundle
`

#### 1.3 Develop the new bundle

Create all Documents, [Services](http://symfony.com/doc/current/book/service_container.html), Events, Event Listeners, [Controllers](http://symfony.com/doc/current/book/controller.html), [Commands](http://symfony.com/doc/current/bundles/SensioGeneratorBundle/commands/generate_command.html) and HTML Twig templates as needed, following the bundles structured defined by Symfony.

#### Example:

* [Cmar/SonarBundle](../src/Pumukit/Cmar/SonarBundle). It is a new custom-Bundle developed by the Cmar organization for "Sonar" project Micro-site creation


