# Migration Guide (From 2.3 to 2.4)

*This page is updated to the PuMuKIT 2.4.0 version*

Here we describe the process to upgrade from PuMuKIT 2.3 to PuMuKIT 2.4. If you are trying to upgrade from PuMuKIT 1, please check out [this guide](from1.7to2.1.md) instead of [this_guide](from2.1to2.3.md).

1. First let's open the PuMuKIT 2 folder on our terminal.

    ```bash
    cd /path/to/pumukit
    ```
2. Then we checkout the most recent stable-release for the 2.4 version. (In this case the 2.4.0 tag)

    ```bash
    git checkout 2.4.0
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
    php doc/upgrade/other_versions/updateModel_2.3_to_2.4.php update:model:2.3to2.4 --env=prod
    ```
6. Drop and create indexes in MongoDB (It should take 30 seconds):

    ```bash
    php app/console doctrine:mongodb:schema:drop --index
    time php app/console doctrine:mongodb:schema:create --index
    ```
7. Sync repository in case it was not in production:

    ```bash
    php app/console pumukit:sync:repository
    ```
8. Clean cache:

    ```bash
    php app/console cache:clear && php app/console cache:clear --env=prod
    ```
