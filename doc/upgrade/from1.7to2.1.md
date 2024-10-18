# Migration Guide (From 1.7 to 2.1)

*This page is updated to the PuMuKIT 2.1.0 version*

The migration from PuMuKIT 1.7 to PuMuKIT 2.1 implies the installation of a PuMuKIT 2.1 instance and the migration of all the content (Multimedia Objects, Series,etc...) in the PuMuKIT 1.7 instance to the new PuMuKIT 2.1 instance. Content in PuMuKIT is built of essences (video files, pictures, etc...) and Metadata (the descriptive data like title, author, etc…). Essences are stored on a filesystem folders structure and Metadata in an internal database.

The migration procedures described here includes the migration of the Essences and Metadata.

## Step 1: PuMuKIT 2.1 Installation

Install PuMuKIT 2.1 in a new system following the steps at the [Installation Guide](../InstallationGuide.md).


## Step 2: Essences Migration

The migration of the PuMuKIT essence-folders is very straight forward as PuMuKIT 1.7 and PuMuKIT 2.1 are totally compatible at that level.

Copy all the files and folders from the two PuMuKIT1.7 essence-folders to PuMuKIT 2.1 ones following the rules:

`web/almacen/`  (PuMuKIT 1.7)  ->  `web/storage/`  (PuMuKIT 2.1)

`web/uploads/`  (PuMuKIT 1.7)  ->  `web/uploads/`  (PuMuKIT 2.1)

If your essence-folders are stored in a NAS (Network Storage System) just adjust the mounting points properly in your new PuMuKIT 2.1 deployment and all will be done at this level.

## Step 3: Metadata Migration

### Metadata export from PuMuKIT1.7

There are a series of scripts to export metadata from PuMuKIT1.7 These scripts
exports all metadata from all the series in PuMuKIT1.7 database, including its
multimedia objects metadata.

To export this metadata, install these scripts and export the metadata into your
PuMuKIT1 server. In order to do that, follow the export guide of the [PuMuKIT1-data-export
repository](https://github.com/campusdomar/PuMuKIT1-data-export/blob/1.0.0/README.md).

Note: Some minor metadata tables are not included in the automatic migration scripts, the metadata from PuMuKIT Users (Publishers, Administrators, etc…), Live Channels and Live Events will not be migrated.
For a complete migration, these metadata must be introduced using the back-office of PuMuKIT2 (http://{PuMuKIT2-Server}/admin).

### Metadata import to PuMuKIT2

To import the metadata into PuMuKIT2 you can use the PuMuKIT2-import-bundle. This bundle allows you to import the metadata exported in the format of the PuMuKIT1-data-export repository into the PuMuKIt2 database.

To import this metadata exported from PuMuKIT 1.7, install this bundle into your PuMuKIT2 instance and
execute the importation following the steps at [PuMuKIT2-import-bundle README](https://github.com/campusdomar/PuMuKIT2-import-bundle/blob/1.0.0/README.md).

## Step 4: Final check

As a recommendation, check you have the same number of Series and Multimedia
Objects in both systems.

#### PuMuKIT1

```bash
$ mysql -u YOUR_PUMUKIT1.7_USER -p
mysql> use YOUR_PUMUKIT1.7_DATABASE
mysql> select count(*) from serial;
mysql> select count(*) from mm;
```

#### PuMuKIT2

```bash
$ mongo
> use YOUR_PUMUKIT2_DATABASE
> db.Series.count()
> db.MultimediaObject.count()
```
