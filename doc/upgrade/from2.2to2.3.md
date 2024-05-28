# Migration Guide (From 2.2 to 2.3)

*This page is updated to the PuMuKIT 2.3.0 version*

Here we describe the process to upgrade from PuMuKIT 2.2 to PuMuKIT 2.3. If you are trying to upgrade from PuMuKIT 1, please check out [this guide](from1.7to2.1.md) instead of [this_guide](from2.1to2.2.md).

1. First let's open the PuMuKIT 2 folder on our terminal.

    ```bash
    cd /path/to/pumukit
    ```
2. Then we checkout the most recent stable-release for the 2.3 version. (In this case the 2.3.0 tag)

    ```bash
    git checkout 2.3.0
    ```
3. We should delete vendor folder and then run composer install (composer should be installed globally, if not check out the [composer docs](https://getcomposer.org/doc/00-intro.md))

    ```bash
    rm -rf vendor/
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
    php doc/upgrade/other_versions/updateModel_2.2_to_2.3.php update:model:2.2to2.3 --env=prod
    ```
