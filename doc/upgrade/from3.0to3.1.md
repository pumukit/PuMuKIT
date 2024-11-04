PuMuKIT Migration Guide
=======================

### Current version

PuMuKIT 3.0.*

### Next version

PuMuKIT 3.1.*

## Steps

1. Connect to MongoDB and execute the following sentence

```mongo
db.MultimediaObject.update({'islive': true},{$set: {'type': 4}}, {multi:true});
```
