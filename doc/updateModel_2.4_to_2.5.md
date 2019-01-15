Upgrading model 2.4.x to 2.5.x
==============================

New feature are available on PuMuKIT 2.5.x. All videos and series will have numerical ID to search in UNESCO catalogue easily.

To generate all numerical ID on database you will execute the following scripts on MongoDB:

### Step 1: Create index

```bash
db.Series.createIndex( { "numerical_id": 1 }, {name: "numericalID"})
db.MultimediaObject.createIndex( { "numerical_id": 1 }, {name: "numericalID"})
```

### Step 2: Select your option

#### Case 1: You have PuMuKIT 1 ID

```bash
db.Series.find({'properties.pumukit1id': {$exists: 1}, 'numerical_id': {$exists: false}}).snapshot().forEach(function(s) {
    db.Series.update({'_id': s._id}, {$set: {'numerical_id': NumberLong(s.properties.pumukit1id)}});
});

db.MultimediaObject.find({'properties.pumukit1id': {$exists: 1}, 'numerical_id': {$exists: false}}).snapshot().forEach(function(m) {
    db.MultimediaObject.update({'_id': m._id}, {$set: {'numerical_id': NumberLong(m.properties.pumukit1id)}});
});
```

#### Case 2: Initialize numerical ID without PuMuKIT 1 ID

```bash
var nextNumericalID = 1;
db.MultimediaObject.find({'numerical_id': {$exists :false},'status': {$ne: -2}, 'properties.pumukit1id': {$exists: false}}).forEach(function(mm) {
      mm['numerical_id'] = NumberLong(nextNumericalID);
      db.MultimediaObject.save(mm);
      nextNumericalID = nextNumericalID + 1;
});
var nextNumericalID = 1;
db.Series.find({'numerical_id': {$exists :false},'status': {$ne: -2}, 'properties.pumukit1id': {$exists: false}}).forEach(function(ss) {
      ss['numerical_id'] = NumberLong(nextNumericalID);
      db.Series.save(ss);
      nextNumericalID = nextNumericalID + 1;
});
```

### Step 3: Generate numerical ID from videos and series without PuMuKIT 1 ID

```bash
db.MultimediaObject.find({'numerical_id': {$exists:1},'status': {$ne: -2}}).sort({'numerical_id': -1}).limit(1).forEach(function(m) {
    var nextNumericalID = m['numerical_id'] + 1;

    db.MultimediaObject.find({'numerical_id': {$exists :false},'status': {$ne: -2}, 'properties.pumukit1id': {$exists: false}}).forEach(function(mm) {
          mm['numerical_id'] = NumberLong(nextNumericalID);
          db.MultimediaObject.save(mm);
          nextNumericalID = nextNumericalID + 1;
    });
});
db.Series.find({'numerical_id': {$exists:1},'status': {$ne: -2}}).sort({'numerical_id': -1}).limit(1).forEach(function(s) {
    var nextNumericalID = s['numerical_id'] + 1;

    db.Series.find({'numerical_id': {$exists :false},'status': {$ne: -2}, 'properties.pumukit1id': {$exists: false}}).forEach(function(ss) {
          ss['numerical_id'] = NumberLong(nextNumericalID);
          db.Series.save(ss);
          nextNumericalID = nextNumericalID + 1;
    });
});
```

[OPTIONAL]

If there are some errors you can clean all numerical ID using the following commands and then you can reexecute above commands.

```bash
db.MultimediaObject.update({'numerical_id': {$exists:1}}, {'$unset': {'numerical_id': ''}}, {multi:true});
db.Series.update({'numerical_id': {$exists:1}}, {'$unset': {'numerical_id': ''}}, {multi:true});
```