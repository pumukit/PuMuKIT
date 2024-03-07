# CHANGELOG

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)

To get the diff for a specific change, go to https://github.com/pumukit/PuMuKIT/commit/XXX where XXX is the change hash.
To get the diff between two versions, go to https://github.com/pumukit/PuMuKIT/compare/3.0.0...3.1.x.

## [4.2.0](https://github.com/pumukit/PuMuKIT/compare/4.1.0...4.2.0) - (2024-10-10)

#### Added
- Added property updatedAt on MultimediaObject document

#### Fixed
- Reported issues

## [4.1.0](https://github.com/pumukit/PuMuKIT/compare/4.0.0...4.1.0) - (2024-03-xx)

#### Added

- Add support for multiple types of media (docs, images, audio, video, external, etc.).
- Added select files from server on wizard.
- Added create external video on series.
- Added email filter on user lists.

#### Changed

- Refactor schema for multimedia objects to support multiple types of media.
- Unify wizard and inbox are now unified in a single upload page.
- Rename of "Wizard" to "Upload" inside series.

#### Removed

- Remove list all multimedia objects menu on backoffice ( legacy feature ).
- Remove support BC for PHP 7.4 or lower.
- Remove "New" option inside series.
- Remove simple wizard

## [4.0.0](https://github.com/pumukit/PuMuKIT/compare/3.9.0...4.0.0) - (2023-07-04)

#### Added
- Added GitHub Actions workflows.
- Added Symfony Flex.
- Added new Symfony components.
- Added PaellaPlayer v7 to core.
- Update code to make it compatible with PHP 7.4
- Update code to make it compatible with PHP 8.2
- Added val translations.
- Added new upload method using TUS protocol on core.
- Added new inbox upload page with drag and drop and auto import feature.
- Update 3rd party libraries to allow use PHP8.

#### Changed
- Update Symfony framework from 3.4 to 5.4 using new structure.
- Replace BotDetectBundle by CrawlerDetect
- Replace MongoDB objects (MongoId(), MongoDate(), ...) by new generic objects defined by namespaces.
- Replace deprecated FOSUserBundle by custom login.
- Changed PUMUKIT_HOST env to PUMUKIT_FRONTEND_HOST

#### Removed
- Remove Travis workflows.
- Remove JWPlayer basic player from core.
- Remove deprecated MobileDetectBundle.
- Remove untranslated languages.

#### Fixed
- Reported issues

#### Security
- Added latest stable versions of 3rd party libraries.

## [3.9.x](https://github.com/pumukit/PuMuKIT/compare/3.8.0...3.9.0) - (2022-07-14)

#### Added
- Access personal series from external bundles.
- Archive multimedia objects.
- License on multimedia object template.

## [3.8.x](https://github.com/pumukit/PuMuKIT/compare/3.7.0...3.8.0) - (2022-01-24)

#### Added
- Added new feature on inbox to upload on a new folder o default inbox folder.
- New envs to configure docker PHP FPM.
- Soap extension by default on docker.

#### Changed
- Convert texti18n subseries to textareai18n.

#### Fixed
- API filter by date range
- API filter by user
- Email HTML header and alternative text email

## [3.7.x](https://github.com/pumukit/PuMuKIT/compare/3.6.0...3.7.x) - (2021-09-15)

#### Added
- Added auto create personal series for each user using a new permission on permission profile.
- Added new feature of headers and tails on multimedia objects.
- Added confirmation when remove multimedia object on UNESCO catalogue.
- Added new feature cookie banner.

#### Fixed
- Fix search multimedia object years filter.
- Fix upload series image with the same name.
- Fix internationalize events default name.

## [3.6.0](https://github.com/pumukit/PuMuKIT/compare/3.5.0...3.6.0) - (2021-04-21)

#### Added
- Added configuration to send notification when user is a co owner of Multimedia Object.
- Added new method to get events from date.
- Added confirmation when delete events.
- Added configuration to set max time execution on jobs.
- Added functionality to use external url on events as iframes.

#### Fixed
- Fix load notification service.
- Fix remove pic from MultimediaObject when is used on series.
- Fix save material name from advance upload form.
- Fix upload files with special characters on name.
- Fix modify broadcast from series.
- Fix test.
- Fix increase views on multimedia objects multi stream when have 1 screen track.
- Fix APIRecorded to filter prototypes and events.

#### Security
- Update composer packages

## [3.5.0](https://github.com/pumukit/PuMuKIT/compare/3.4.0...3.5.0) - (2020-07-21)

#### Added
- Added new permissions to edit multimedia object
- Add a loading spinner and disabled "upload" button on wizard to know when wizard are uploading file
- Add logger on generate new tracks
- Add new param to show and basic editable video interface on WebTV portal.

#### Fixed
- Remove cancel button on wizard

## [3.4.0](https://github.com/pumukit/PuMuKIT/compare/3.3.0...3.4.0) - (2020-06-18)

#### Added
- Added CA translations

#### Fixed
- Fixed design on basic live chat
- Fixed method to override a material file

## [3.3.0](https://github.com/pumukit/PuMuKIT/compare/3.2.0...3.3.0) - (2020-03-30)

#### Added
- Added method to override a material file added to use external API
- Update PHP Alpine version on docker
- Use cache to install composer dependencies

#### Fixed
- Fixed embeddedSegments toString method
- Fixed Pic toString method

## [3.2.0](https://github.com/pumukit/PuMuKIT/compare/3.1.0...3.2.0) - (2020-03-09)

#### Added
- New logic to read mp properties from Galicaster mediapackages in Opencast
- Optional notification sending to OpencastBundle when a mediapackage has been imported
- Hide/show eye tooltip to multimedia object list of tracks in the back-office
- Script that syncs existing PuMuKIT series with Opencast (opencast:sync:series)
- Minor design improvements to the back-office lists and naked view

#### Fixed
- Edge-case where removing a cloned object after changing the user would remove the original attached image.
- Live events issue related to using mongo driver version 1.4.5 or lower

## [3.1.0](https://github.com/pumukit/PuMuKIT/compare/3.0.0...3.1.0) - (2019-09-10)

#### Added
- Fields to Series template to predefined values on MultimediaObjects
- PuMuKIT and docker ENVS
- External iFrames on MultimediaObject
- Wall block to use on web portal
- Interfaces and traits on Tag and Person documents
- PuMuKIT logo on web portal
- PuMuKIT PHP-ext required on composer.json

#### Changed
- PuMuKIT to the new [repository]((https://github.com/pumukit/PuMuKIT) on github
- Moved deactivated core bundles to [new repository](https://github.com/pumukit)
- Update Resources/doc/Configuration.md of all bundles
- Updating code to use Doctrine ODM 2.0

#### Removed
- Broadcast code references
- Unused files ( InterfaceTest, default files, ... )
- Changelog info of versions lower than PuMuKIT 3.0

#### Fixed
- Reported issues

#### Security
- Add maximum number of login attempts to increase security


## [3.0.0](https://github.com/campusdomar/PuMuKIT2/compare/2.6.0...3.0.0) - (2019-06-10)

#### Added
- New web portal design
- AGPL License on PuMuKIT
- Magic URL behavior
- Support to multiple inbox
- Dockers

#### Changed
- Updated PHP technology stack (PHP7, Symfony 3.4...)
- Rewrite generic code
- PuMuKIT filters
- isLive MultimediaObject attribute to new MultimediaObject type.

#### Removed
- Copyright and license on Series
- JWPlayer multi stream support

#### Fixed
- Reported issues
