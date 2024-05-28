# Migration Guide (From 2.4 to 2.5)

*This page is updated to the PuMuKIT 2.5.0 version*

Here we describe the process to upgrade from PuMuKIT 2.4 to PuMuKIT 2.5.

1. First let's open the PuMuKIT 2 folder on our terminal.

    ```bash
    cd /path/to/pumukit
    ```
2. Then we checkout the most recent stable-release for the 2.5 version.

    ```bash
    git checkout 2.5.0
    ```
3. We should delete vendor folder and then run composer install (composer should be installed globally, if not check out the [composer docs](https://getcomposer.org/doc/00-intro.md))

    ```bash
    rm -rf vendor/
    composer install
    ```
4. Connect to MongoDB and execute the queries of 
    
    [PuMuKIT-Migration-2.5](https://github.com/campusdomar/PuMuKIT2/blob/2.5.x/doc/updateModel_2.4_to_2.5.md)
