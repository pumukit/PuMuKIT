# CHANGELOG

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)

To get the diff for a specific change, go to https://github.com/pumukit/PuMuKIT/commit/XXX where XXX is the change hash.
To get the diff between two versions, go to https://github.com/pumukit/PuMuKIT/compare/3.0.0...3.1.x.

## [Unreleased]

#### Added

- Versioned read-only REST API based on API Platform 4 under `/api/v1`, with Swagger UI and ReDoc, protected by the API access permission and exposing MultimediaObject, Series, Tag and Person collections.
- Mailpit as a mail catcher for local development (web inbox at http://localhost:8025).
- Functional tests for user creation, login and logout.
- `make php-shell` and `make composer` targets to work inside the PHP container.
- `pumukit:textindex:rebuild` command to regenerate the search text index for all Multimedia Objects and Series.

#### Changed

- Redesign the admin dashboard: drop the SIMILE Timeline 2.3.1 widget (and its `/dashboard/series/timeline.xml` feed) in favour of a Chart.js layout. The default view renders KPI cards (series, multimedia objects, total time, storage usage), a publication-activity bubble chart (date × 4-hour buckets, fed by a new `StatsService::getMmobjActivityByDayHour` aggregation over `properties.created`) with a day/month/year toggle that windows to the last 30 days / 12 months / 10 years, storage occupancy as HTML progress bars per `dir_out`, and a Last-5-series panel with owner username. The `?show_stats=1` gate is removed: everything renders on the default route. Scope-aware: users without `ROLE_SCOPE_GLOBAL` get storage hidden and their stats / recent series filtered by `properties.owners`. Chart.js (+ `chartjs-adapter-date-fns` + `chartjs-plugin-zoom`) is introduced as the planned successor for the D3 v3 + NVD3 stack still used in `StatsUIBundle`.
- Upgrade the core to Symfony 6.4 LTS.
- Single source of truth for the Symfony version through `extra.symfony.require` (Symfony components pinned with `*`).
- Migrate the security layer from the legacy Guard system to the new authenticator system (existing sha512 passwords remain valid).
- Replace Swiftmailer with `symfony/mailer`.
- Migrate controllers from sensio annotations to native PHP 8 attributes (`#[Template]`, `#[IsGranted]`, `#[MapDocument]`).
- Move PaellaPlayerBundle and StatsUIBundle into the core; they are no longer external packages.
- Upgrade Pagerfanta to v4 and MobileDetect to v4.
- Upgrade PHPUnit to 10 and migrate its configuration to the new schema.
- Upgrade the MongoDB stack: server 5.0 → 8.0, `ext-mongodb` 1.14 → 1.21, `mongodb/mongodb` 1.13 → 1.21 and Doctrine ODM 2.6 → 2.15.
- Update composer dependencies (gedmo/doctrine-extensions, php-cs-fixer, pagerfanta adapters, etc.).

#### Fixed

- Several Symfony 6 runtime issues in the backoffice menu, the web profiler and the pagination adapters.
- Media API endpoint failing on multimedia objects whose metadata was stored as a JSON array.
- Video playback returning a 404 for local tracks: they are now served through `/trackfile` instead of redirecting to the blocked `/storage` path.
- Image-type multimedia objects returning a 404 on the player: the image now goes through `track_url()` (i.e. `/trackfile`) like documents and video tracks, instead of the raw `/storage/downloads/...` URL.
- Already-developed images (JPG/PNG/…) being run through the `image_raw_broadcastable` profile and rendered with darktable's default development modules (washed-out / blown-out output). The default image profile for a target now picks `image_raw` only when the master is actually a camera RAW file (extensions in `ImageRawUtils`); otherwise it uses the generic `image` profile. Set `target_default_profiles.<TARGET>.image_raw` next to `image` in `encoder.yaml` to opt in.

#### Removed

- API Platform 2.7 (replaced by 4.x), removing the two GraphQL security advisories and their audit ignores.
- `sensio/framework-extra-bundle` (abandoned).
- `symfony/templating` (unused), Swiftmailer and `symfony/swiftmailer-bundle`.
- The v4→v5 data migration commands (`pumukit:upgrade:*`); 6.0 is reached from 5.1.x, which already runs the v5 schema.
- Vendored SIMILE Timeline 2.3.1 assets (~1 MB) under `NewAdminBundle/Resources/public/js/timeline_2.3.1/`.
- Unused fields from the encoder profile configuration schema (`format`, `codec`, `mime_type`, `bitrate`, `framerate`, `channels`, `app`, `rel_duration_size`, `rel_duration_trans`, `file_cfg`, `prescript`, and `streamserver.{name,type,host,description}`); these were declared but never read by the application. `validateProfilesDir()` now identifies the failing profile by its array key in its error message instead of the redundant `streamserver.name`.


## [5.1.0](https://github.com/pumukit/PuMuKIT/compare/4.0.0...5.0.0) - (2026-03-06)

#### Added

- Iframes works now loading first de thumbnail and when user click on it, the iframe is loaded and the video starts to play.
- Add new env to configure the token expiration time on trackfile.

#### Fixed

- Create token on trackfile to prevent WSTG-ATHN-04.
- Added properties to multimedia object when use archive feature.


## [5.0.0](https://github.com/pumukit/PuMuKIT/compare/4.0.0...5.0.0) - (2025-02-24)

#### Added

- Add support for multiple types of media (docs, images, audio, video, external, etc.).
- Add players for each type of media.
- Added select files from server on wizard.
- Added create external video on series.
- Added email filter on user lists.
- Added STOP job option.
- Added events count on series list.

#### Changed

- Refactor schema for multimedia objects to support multiple types of media.
- Unify wizard and inbox are now unified in a single upload page.
- Unify edit and info option on advance edit multimedia object files.
- Rename of "Wizard" to "Upload" inside series.
- Rename base encoder profiles.

#### Removed

- Remove list all multimedia objects menu on backoffice ( legacy feature ).
- Remove support BC for PHP 7.4 or lower.
- Remove "New" option inside series.
- Remove simple wizard
- Remove download logs from backoffice.
- Remove unused encoder profile elements.

## [4.2.0](https://github.com/pumukit/PuMuKIT/compare/4.1.0...4.2.0) - (2024-10-10)

#### Added
- Added property updatedAt on MultimediaObject document

#### Fixed
- Reported issues

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
