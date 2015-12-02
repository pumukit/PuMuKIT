# Override WebTVBundle manual

Overriding the PumukitWebTVBundle allows you to change:

* Footer
* Header


## Process

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
