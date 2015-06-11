AdminGuide
==========

Steps before the live event: How to enable and configure the chat
-----------------------------------------------------------------

1. Check there are no messages for the live channel

   - Connect to your database (pumukit in this example) in mongo:

     ```
     $ mongo
     > use pumukit
     ```

   - Guess the live channel our event will used. We must know the `name` of our Event (available on `http://{YourPuMuKITLiveChannel}/admin/event/` as `Text` or `Event`). In this example `Event of today` we have an event with name `Event of today`:

     ```
     > db.Event.find({"name": "Event of today"}).pretty()
     ```

     Output example:

     ```
    {
        "_id" : ObjectId("55757dda6e4cd6525e8b4567"),
        "live" : DBRef("Live", ObjectId("556ebf7372613ad7148b4567")),
        "name" : "New Event 1",
        "place" : "Vigo",
        "date" : ISODate("2015-06-08T11:00:00Z"),
        "duration" : 120,
        "display" : true,
        "create_serial" : true
    }
     ```

   - We take the `live` id (in this example, 556ebf7372613ad7148b4567) and we search for all the messages with this channel id:

    ```
    > db.Message.count({"channel": "556ebf7372613ad7148b4567"})
    ```

   - If the count is not 0 and we want to save this data. You should use the same channel id as in the previous step, 556ebf7372613ad7148b4567 in this example. You should also chose the directory and filename where to export the messages, /home/pumukit/dirtoexport/messages_556ebf7372613ad7148b4567.json as an example:

    ```
    > exit
    $ mongoexport -d pumukit -c Message -q "{\"channel\": \"556ebf7372613ad7148b4567\"}" --out /home/pumukit/dirtoexport/messages_556ebf7372613ad7148b4567.json
    ```

   - Now that the messages are saved, you can delete all the messages of this channel:

    ```
    $ mongo
    > use pumukit
    > db.Message.remove({"channel": "556ebf7372613ad7148b4567"})
    > exit
    ```

2. Set the `pumukit_cmar_live_chat.enable` parameter to `true` in your `src/Pumukit/Cmar/LiveBundle/Resources/config/config.yml` file to enable the chat:

   ```
   parameters:
       ...
       pumukit_cmar_live_chat.enable: true
       ...
   ```

3. Set the `pumukit_cmar_live_chat.update_interval` paramater with the desired milliseconds in your `src/Pumukit/Cmar/LiveBundle/Resources/config/config.yml` file to configure the refresh interval of the chat:

   ```
   parameters:
       ...
       pumukit_cmar_live_chat.update_interval: 5000
       ...
   ```

4. Clear the cache. For production environment:
   
   ```
   $ php app/console cache:clear --env=prod --no-debug
   ```

5. Go the live channel event `http://{YourPuMuKITLiveChannel}/live/{liveId}` or just `http://{YourPuMuKITLiveChannel}/live` if you only have one live channel broadcasting and write with your name:

   ```
   Here you can write your questions for the speaker
   ```

Steps after the live event: How to export chat messages and disable the chat
----------------------------------------------------------------------------

1. Export chat messages

   - Connect to your database (pumukit in this example) in mongo:

     ```
     $ mongo
     > use pumukit
     ```

   - Guess the live channel our event will used. We must know the `name` of our Event (available on `http://{YourPuMuKITLiveChannel}/admin/event/` as `Text` or `Event`). In this example `Event of today` we have an event with name `Event of today`:

     ```
     > db.Event.find({"name": "Event of today"}).pretty()
     ```

     Output example:

     ```
    {
        "_id" : ObjectId("55757dda6e4cd6525e8b4567"),
        "live" : DBRef("Live", ObjectId("556ebf7372613ad7148b4567")),
        "name" : "New Event 1",
        "place" : "Vigo",
        "date" : ISODate("2015-06-08T11:00:00Z"),
        "duration" : 120,
        "display" : true,
        "create_serial" : true
    }
     ```

   - We take the `live` id (in this example, 556ebf7372613ad7148b4567) and we chose the directory and filename where to export the messages, /home/pumukit/dirtoexport/messages_556ebf7372613ad7148b4567.json as an example:

    ```
    > exit
    $ mongoexport -d pumukit -c Message -q "{\"channel\": \"556ebf7372613ad7148b4567\"}" --out /home/pumukit/dirtoexport/messages_556ebf7372613ad7148b4567.json
    ```

    - Now that the messages are saved, you can delete all the messages of this channel:

    ```
    $ mongo
    > use pumukit
    > db.Message.remove({"channel": "556ebf7372613ad7148b4567"})
    > exit
    ```

2. If there aren't other live channels with live events on that moment, set the `pumukit_cmar_live_chat.enable` parameter to `true` in your `src/Pumukit/Cmar/LiveBundle/Resources/config/config.yml` file to enable the chat:

   ```
   parameters:
       ...
       pumukit_cmar_live_chat.enable: true
       ...
   ```

3. Clear the cache. For production environment:
   
   ```
   $ php app/console cache:clear --env=prod --no-debug
   ```
