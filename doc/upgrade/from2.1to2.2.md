# Migration Guide (From 2.1 to 2.2)

*This page is updated to the PuMuKIT 2.2.0 version*

Here we describe the process to upgrade from PuMuKIT 2.1 to PuMuKIT 2.2. If you are trying to upgrade from PuMuKIT 1, please check out [this guide](from1.7to2.1) instead.

1. First let's open the PuMuKIT 2 folder on our terminal.

    ```bash
    cd /path/to/pumukit
    ```
2. Then we checkout the most recent stable-release for the 2.2 version. (In this case the 2.2.0 tag)

    ```bash
    git checkout 2.2.0
    ```
3. We run composer install (composer should be installed globaly, if not check out the [composer docs](https://getcomposer.org/doc/00-intro.md))

    ```bash
    composer install
    ```
4. We execute these commands to update our model classes.

    ```bash
    php app/console doctrine:mongodb:schema:create
    php app/console doctrine:mongodb:schema:update
    ```
5. Finally, we run these commands to update our existing database with the latest changes.

    ```bash
    php app/console pumukit:init:repo role --force
    php app/console pumukit:init:repo permissionprofile --force
    php doc/upgrade/other_versions/updateModel_2.1_to_2.2.php update:model:2.1to2.2
    ```

## Notes
* There were major changes in the WebTVBundle (including a new responsive design). If you have a custom WebTVBundle for the 2.1 version, check our [Legacy WebTVBundle Documentation](https://github.com/campusdomar/PuMuKIT2/tree/2.2.x/src/Pumukit/Legacy/WebTVBundle/Resources/doc) for instructions on how to keep it working with the 2.2 version

* The PuMuKIT 2.2 version comes with soft-editing capabilities. Check the [Video Editor Bundle](https://github.com/teltek/PuMuKIT2-video-editor-bundle) to know more about it.
