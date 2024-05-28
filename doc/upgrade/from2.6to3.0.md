# Migration Guide (From 2.6 to 3.0)

*This page is updated to the PuMuKIT 3.0.0 version*


1. Delete this configuration on your parameters_deploy.yml

```
pumukit.inspection.command.ffmpeg
pumukit.inspection.command.libav
pumukit.picextractor.command.ffmpeg
pumukit.picextractor.command.libav
```

2. Connect to MongoDB and execute the following lines

```mongo
db.MultimediaObject.update({'islive': true},{$set: {'type': 4}}, {multi:true});
```
