# Migration Guide (From 4.0 to 5.0)

There are 3 steps to migrate PuMuKIT from 4.0 to 5.0:

You should have a backup of your database before starting the migration process.

1. First let's open the PuMuKIT 4 docker PHP on our terminal.

    ```bash
    docker exec -it {php-docker-container-name} bash
    ```

2. Execute the following command to update the PuMuKIT 4 to 5.

First command will update the schema of the external links added on PuMuKIT 4 or lower. It will be converted to a new type of schema like MediaInterface.

    ```bash
    php bin/console pumukit:upgrade:schema:external --force
    ```

Second command will update the schema of the other tracks of PuMuKIT 4 or lower. Videos and audios will be converted with the new schema and multimedia objects
with type "New" ( multimedia objects without files added ) will be converted to video types.

    ```bash
    php bin/console pumukit:upgrade:schema:track --force
    ```

Third command will execute the "ffprobe" of all video/audio tracks to get all metadata of the tracks and store it in the database.

    ```bash
    php bin/console pumukit:upgrade:metadata:track --force
    ```
