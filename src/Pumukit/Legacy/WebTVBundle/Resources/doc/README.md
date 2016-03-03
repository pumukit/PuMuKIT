#PuMuKIT Legacy WebTV Bundle#
The goal of this bundle is to provide support to those bundles which inherited from the PuMuKIT 2.1 WebTVBundle but keeping the ability to upgrade their PuMuKIT platform to the latest 2.2 version.

## How to set your 2.1 bundle to be 2.2 compatible
### Step 1: Open the app/AppKernel:
 * Find this line:
    ```php
    new Pumukit\WebTVBundle\PumukitWebTVBundle(),
    ```
 * Then change it with this one:
    ```php
    new Pumukit\Legacy\WebTVBundle\PumukitWebTVBundle()
    ```

This will swap the original WebTVBundle by our Legacy Bundle.


If your inherited bundle did not override any controllers from the WebTVBundle, then you are all set! Congratulations!

### Step 2: Change Controllers use statements

You will have to open every controller file you overrided and change every use statement where you import a WebTVBundle class by its LegacyWebTVBundle counterpart.

####Example:

Replace this:
```php
<?php
namespace Pumukit\Example\WebTVBundle\Controller;

//replace the line below
use Pumukit\WebTVBundle\Controller\IndexController as Base;
//replace the line above
...

class IndexController extends Base
{
...
```
By this:
```php
<?php
namespace Pumukit\Example\WebTVBundle\Controller;

//with this line (note the extra 'Legacy')
use Pumukit\Legacy\WebTVBundle\Controller\IndexController as Base;
//with the line above
...

class IndexController extends Base
{
...
```
