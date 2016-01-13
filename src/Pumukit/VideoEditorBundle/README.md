# Video Editor Bundle for the PuMuKIT 2 platform #
Short explanation. What is this bundle? What is it for?
# WORK IN PROGRESS #
## Installation ##

### Step 1: Introduce repository in the root project composer.json ###
Open a command console, enter your project directory and execute the following command to add this repo:

```
composer config repositories.pumukitvideoeditorbundle vcs http://gitlab.teltek.es/pumukit2/videoeditorbundle.git
```

### Step 2: Download the Bundle ###
Open a command console, enter your project directory and execute the following command to download the latest stable version of this bundle:

```
composer require teltek/pmk2-videoeditor-bundle dev-master
```

### Step 3: Install the Bundle ###
Install the bundle by executing the following line command. This command updates the Kernel to enable the bundle (app/AppKernel.php) and loads the routing (app/config/routing.yml) to add the bundle routes.

```
 php app/console pumukit:install:bundle Pumukit/YoutubeBundle/PumukitYoutubeBundle
 ```
