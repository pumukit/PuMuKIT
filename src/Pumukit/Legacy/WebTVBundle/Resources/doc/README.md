#PuMuKIT Legacy WebTV Bundle#
The goal of this bundle is to provide support to those bundles which inherited from the PuMuKIT 2.1 WebTVBundle but keeping the ability to upgrade their PuMuKIT platform to the latest 2.2 version.

## How to set your 2.1 bundle to be 2.2 compatible
### Step 1: Change the bundle on the AppKernel.php
* On the pumukit root dir:
```bash
sudo sed -i "s/new Pumukit\\\\WebTVBundle\\\\PumukitWebTVBundle()/new Pumukit\\\\Legacy\\\\WebTVBundle\\\\PumukitWebTVBundle()/g" app/AppKernel.php
```

This will swap the original WebTVBundle by our Legacy Bundle.


If your inherited bundle did not override any controllers from the WebTVBundle, then you are all set! Congratulations!

### Step 2: Change Controllers use statements

You will have to execute this sed command to replace all 'use' statements to the 'Legacy' namespace for all your controllers.

* On your bundle root dir:
```bash
sudo sed -i "s/use Pumukit\\\\WebTVBundle\\\\\Controller/use Pumukit\\\\Legacy\\\\WebTVBundle\\\\\Controller/g" Controller/*
```
