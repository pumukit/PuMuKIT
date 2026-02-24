# Migration Guide (From 4.0 to 5.0)

There are 3 steps to migrate PuMuKIT from 4.0 to 5.0:

You should have a backup of your database before starting the migration process.

1. First, let's open the PuMuKIT 4 docker PHP in our terminal.

    ```bash
    docker exec -it {php-docker-container-name} bash
    ```

2. Execute the following command to check if all videos, images and materials exist and can be upgraded.

    ```bash
    php bin/console pumukit:upgrade:check --storage --pics --materials
    ```

3. Fix all results deleting referent for each track, pic or material that does not exist.

4. Execute the following command to update the PuMuKIT 4 to 5.

The first command will update the schema of the external links added on PuMuKIT 4 or lower. It will be converted to a new type of schema like MediaInterface.

    ```bash
    php bin/console pumukit:upgrade:schema:external --force
    ```

The second command will update the track's schema of PuMuKIT 4 or lower. Videos and audios will be converted with the new schema and multimedia objects
with the type "New" (multimedia objects without files added) will be converted to video types.

    ```bash
    php bin/console pumukit:upgrade:schema:track --force
    ```

The third command will execute the "ffprobe" of all video/audio tracks to get all metadata of the tracks and store it in the database.

    ```bash
    php bin/console pumukit:upgrade:metadata:track --force
    ```

### Required Permission to Upload Multimedia Objects in Pumukit 5

In Pumukit 5, users must have the ROLE_UPLOAD_INBOX permission in order to upload multimedia objects.

Although implemented as a role within the system, ROLE_UPLOAD_INBOX functions as a specific permission that grants access to the Upload Inbox, which is required to upload multimedia content.

The ROLE_UPLOAD_INBOX permission must be assigned to the user through the administration interface.
