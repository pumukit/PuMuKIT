# CHANGELOG

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)

To get the diff for a specific change, go to https://github.com/pumukit/PuMuKIT/commit/XXX where XXX is the change hash.
To get the diff between two versions, go to https://github.com/pumukit/PuMuKIT/compare/3.0.0...3.1.x.

## [Unreleased]

#### Added

- Versioned read-only REST API based on API Platform 5 under `/api/v1`, with Swagger UI and ReDoc, protected by the API access permission and exposing MultimediaObject, Series, Tag and Person collections.
- Mailpit as a mail catcher for local development (web inbox at http://localhost:8025).
- RabbitMQ 4.4 service and Symfony Messenger with the AMQP transport (PHP extension `amqp` in the Docker image), ready for the bundles to declare their own transports and queues.
- Functional tests for user creation, login and logout.
- `make php-shell` and `make composer` targets to work inside the PHP container.
- `pumukit:textindex:rebuild` command to regenerate the search text index for all Multimedia Objects and Series.
- `pumukit:check:assets` command to list bundle assets and PuMuKIT templates referenced by templates that no installed bundle provides, removed JavaScript globals still in use, and overridden portal layouts without the importmap.
- Stimulus (`symfony/stimulus-bundle`) and `symfony/ux-translator`, with the importmap and the `pumukit` entrypoint declared by the CoreBundle; bundles register their controllers with `FrontendAssets::register()`.
- Route smoke test (`make test-smoke`, also run by CI): requests every GET route with a full set of fixtures and fails on server errors, on GET requests that modify data and on route parameters it cannot resolve.
- Statistics panel at `/admin/stats` for administrators and teachers: views and unique views, views over time compared with the previous period, origin (portal, embedded player, LMS, other websites), devices, day and hour heatmap and the most viewed videos and series, with the same panel for one series or one video. Teachers only see their own videos.
- Read-only statistics REST API under `/api/v1/stats/` (API Platform), protected by the statistics permission and filtered by the user's scope.
- Daily view statistics updated on every view, and the `pumukit:stats:rollup` command to build them from the existing `ViewsLog`, so that the statistics do not read the raw view log.
- Dashboard rebuilt on the read-only `/api/v1/dashboard/` API: series, multimedia objects and hours of content, views today, multimedia objects added per month, catalogue status and, for administrators, the encoder queue and the storage by disk. Each user only sees their scope.
- `/api/v1/keywords/` API for the keyword autocomplete of the backoffice.

#### Changed

- Redesign the admin dashboard: drop the SIMILE Timeline 2.3.1 widget (and its `/dashboard/series/timeline.xml` feed) in favour of a Chart.js layout. The default view renders KPI cards (series, multimedia objects, total time, storage usage), a publication-activity bubble chart (date × 4-hour buckets, fed by a new `StatsService::getMmobjActivityByDayHour` aggregation over `properties.created`) with a day/month/year toggle that windows to the last 30 days / 12 months / 10 years, storage occupancy as HTML progress bars per `dir_out`, and a Last-5-series panel with owner username. The `?show_stats=1` gate is removed: everything renders on the default route. Scope-aware: users without `ROLE_SCOPE_GLOBAL` get storage hidden and their stats / recent series filtered by `properties.owners`. Chart.js (+ `chartjs-adapter-date-fns` + `chartjs-plugin-zoom`) is introduced as the planned successor for the D3 v3 + NVD3 stack still used in `StatsUIBundle`.
- Upgrade the core to Symfony 7.4 LTS and PHP 8.5.
- Build the Docker image on Debian 13 (trixie) with FFmpeg 8.1 instead of Debian 12 (bookworm) and FFmpeg 6.0, which no longer receive regular security updates.
- Update NGINX to the 1.30 stable branch (1.25 has had no fixes since May 2024) and enable HTTP/2 with the `http2` directive.
- Deliver the files of `/trackfile/` and of the backoffice track download through NGINX with `X-Accel-Redirect` after PHP checks the token and the permissions, instead of sending them from PHP-FPM.
- Use Valkey 9.1 instead of an unpinned Redis image in `docker-compose.yml`: Valkey keeps the BSD license of Redis 7.2 and PuMuKIT uses it as it used Redis.
- Single source of truth for the Symfony version through `extra.symfony.require` (Symfony components pinned with `*`).
- Build the Docker image with `composer install` from the committed `composer.lock` instead of `composer update`, so that an image has the dependencies that CI tested.
- Add a `.dockerignore`: the image no longer copies the local `vendor/`, `var/`, `.git`, generated assets or working notes from the build context.
- Remove the `version` field from `composer.json`: Composer takes the version from the git tag, and `Pumukit\PumukitVersion::VERSION` is the only version to update on a release.
- Migrate the security layer from the legacy Guard system to the new authenticator system (existing sha512 passwords remain valid).
- Replace Swiftmailer with `symfony/mailer`.
- Migrate controllers from sensio annotations to native PHP 8 attributes (`#[Template]`, `#[IsGranted]`, `#[MapDocument]`).
- Move PaellaPlayerBundle and StatsUIBundle into the core; they are no longer external packages.
- Upgrade Pagerfanta to v4 and MobileDetect to v4.
- Upgrade PHPUnit to 13 and migrate its configuration and tests (attributes, stubs). The suite fails on deprecations and PHPUnit notices.
- Upgrade the MongoDB stack: server 5.0 → 8.0, `ext-mongodb` 1.14 → 2.5, `mongodb/mongodb` 1.13 → 2.5, Doctrine ODM 2.6 → 2.17 and DoctrineMongoDBBundle 4.6 → 5.6.
- Map documents with PHP attributes instead of Doctrine annotations; `doctrine/annotations` and `doctrine/cache` are no longer installed.
- Read request parameters from an explicit source (route attributes, query string or body) instead of `Request::get()`.
- Upgrade MonologBundle to 4, API Platform to 5 and phpdocumentor/reflection-docblock to 6.
- Replace the `style` attributes of the core templates with the classes of `bundles/pumukitcore/css/pumukit-utilities.css`, loaded by the portal layout; `pumukit:check:assets` reports an overridden portal layout that does not load it.
- Serve bundle assets through Symfony AssetMapper; jQuery, Bootstrap 3.4.1, moment 2.30.1, jQuery UI and bootstrap-datetimepicker are shared from `bundles/pumukitcore/vendor/`.
- Move the backoffice, the portal, the inbox and the live pages to Bootstrap 5.3.8 without Bootstrap Material Design: the previous look is kept by `backoffice.css` and `webtv.css`, the backoffice messages are Bootstrap 5 toasts and the pages use the system font stack (see UPGRADE-6.0.md).
- Upgrade Font Awesome to 7.3.1, shared by the backoffice (5.11.2) and the portal (4.7) from `bundles/pumukitcore/vendor/fontawesome/`, with the current icon names; `pumukit:check:assets` reports the icons that Font Awesome 7 does not provide.
- Replace the Material Design icons of the backoffice (`mdi-*`) with Font Awesome 7 icons and remove the Material Design Icons font; `pumukit:check:assets` reports the `mdi-*` classes with their equivalent.
- Upgrade jQuery 1.10.2 → 4.0.0 (without jQuery Migrate), jQuery UI 1.11.3 → 1.14.2 and bootstrap-datetimepicker 4.14.30 → 4.17.49; `pumukit:check:assets` reports the jQuery utilities removed in jQuery 4 (`$.trim`, `$.isArray`…).
- Move the inline JavaScript of CoreBundle, EncoderBundle, WizardBundle, WebTVBundle and BaseLivePlayerBundle templates to Stimulus controllers; `@PumukitCore/Upload/uppy_tus.js.twig` and `@PumukitBaseLivePlayer/Advance/Sessions/*.js.twig` are replaced by `uppy_tus.html.twig` and `sessions.html.twig`, `announces_ajax.js` by the `webtv--announces` controller and `template_video.js` by the `webtv--dynamic-pic` controller.
- Remove jQuery Countdown and Modernizr from WebTVBundle; the live player countdown and the mobile detection of the search filters are native.
- Move the StatsUIBundle JavaScript to the `stats-ui--stats` Stimulus controller and replace D3 3 and NVD3 with Chart.js 4.5.1; titles are rendered as text, no longer as HTML.
- Replace Uppy 1 with Uppy 6 behind a generic `core--uploader` Stimulus controller configured with the `uploader_attributes()` Twig function; `uppy_xhr.js.twig`, `common.js.twig`, `uppy_languages.html.twig` and the Uppy 1 bundle are removed.
- Validate in TUS uploads the file name and type that tus-php stores, closing a bypass of the blocked extensions list.
- Keep inbox imports inside the inbox directory: the file name and folder of `/dispatchImport` could point to any readable file of the server.
- Move the roles, series types, live channels, legacy events, users, groups, people, permission profiles, tags, links and inbox explorer zones of the backoffice to Stimulus controllers (`admin--actions`, `admin--modal-form`, `admin--filter-form`, `admin--batch` and zone controllers); `pumukit.js.twig` loses 39 global functions and is cached privately.
- Move the series, playlists and head and tail manager zones of the backoffice, and the create and update forms of the zones already moved, to Stimulus controllers (`admin--meta-form`, `admin--changepub`, `admin--event-tabs`); the head and tail selects of a series no longer stop saving after opening a second series.
- Move the pictures (list, existing pictures, thumbnail generator) and the copy and translate buttons of the i18n fields of the backoffice to Stimulus.
- Move the multimedia object list of the backoffice (rows, pager, batch actions, row menu, archive, job status and preview) to Stimulus controllers (`admin--job-status`, `admin--pic-sync`, `admin--modal-cleanup`).
- Remove unused backoffice code: the multimedia object filter template, `update_preview_mms()`, `procesaReset()`, `dblclick_fila_url()`, the handlers of the multimedia object "New" and template "Cancel" buttons, and the hidden multimedia object social form with its route and action.
- Move the metadata, publication, metadata sync, LMS, properties, head and tail and external player forms of the multimedia object editor to Stimulus controllers (`admin--external-player`).
- Move the tag tab (tree, filter, add and remove) and the links block of the multimedia object editor, also in the UNESCO catalogue, to Stimulus (`admin--tag-picker`); tags added from a search result or an expanded branch in the UNESCO catalogue now refresh its preview and tag menu.
- Move the owners, admin groups and broadcast profile modals of the multimedia object and series editors to Stimulus controllers (`admin--group-lists`, `admin--mm-groups`, `admin--broadcast`); group names are rendered as text.
- Do not save the broadcast of a series when no multimedia object is selected: the request had no `ids` and changed every multimedia object of the series.
- Move the tracks, jobs and materials of the multimedia object editor to Stimulus controllers (`admin--track-list`, `admin--job-priority`); the material uploads answer JSON only and the multimedia object editor has no inline JavaScript left.
- Refresh again the track list every 8 seconds while the multimedia object has pending jobs; it stopped in 5.0.0 because the flag was set inside an included template.
- Move the UNESCO catalogue of the backoffice to the `admin--unesco` Stimulus controller; its fragments work inside project overrides of the catalogue index, and a failed delete or clone shows the server error.
- Move the live events zone of the backoffice to the `admin--live-event` Stimulus controller; series suggestions are rendered as text.
- Move the multimedia objects of a playlist and the add to playlist modals to Stimulus controllers (`admin--playlist-mms`, `admin--playlist-modal`, `admin--playlist-add`); new playlist titles are rendered as text.
- Move the places and precincts zone of the backoffice to the `admin--places` Stimulus controller; place names above the precinct list are rendered as text and the create and update modals show the server error of a conflict.
- Move the slider manager of the backoffice to the `admin--slider` Stimulus controller.
- Move the activity chart of the backoffice dashboard to the `admin--dashboard` Stimulus controller and remove the unused Chart.js zoom plugin.
- Move the series styles manager of the backoffice to the `admin--series-styles` Stimulus controller; saving a style shows the translated message and a failed save shows the error.
- Render the text of a series style escaped in its editor, so a `</textarea>` in the CSS no longer breaks the page, and stop adding the template indentation to the text on every save; a failed series style deletion shows the error.
- Remove the email check of the profile page of the backoffice (`pumukitnewadmin_profile_user_check_user` route and `UserProfileController::checkEmailToUseOnUser()`): the email field is read-only and the form was submitted whatever the check answered.
- Show a warning instead of failing with an error 500 on the profile page of the backoffice when the user has no associated person, and in the video search of the add to playlist modal.
- Do not fail with an error 500 when the video search of the add to playlist modal is empty.
- Replace `@PumukitNewAdmin/pumukit.js.twig` and its routes with the `@pumukit/new-admin/backoffice` importmap entrypoint and the `admin--tabs` Stimulus controller; opening `#myModal` empties only its own content, and the unused `$.ajaxSetup({cache: true})` and `#iframeUpload` NGINX check are removed.
- Remove jQuery Migrate from the backoffice and portal layouts: no core script uses the APIs removed in jQuery 3.
- Remove noUiSlider and `jquery.dropdown.css` from the backoffice layout: no page used them.
- Remove the unminified copies of the portal Bootstrap and Font Awesome styles and of the backoffice Font Awesome styles: no template loaded them.
- Replace materialize-tags, typeahead.js and Bloodhound in the keyword fields of the backoffice with a module of the bundle that keeps the same look.
- Replace moment, bootstrap-datetimepicker and daterangepicker with native date fields in the portal search and the statistics; the predefined ranges of the statistics are translated.
- Replace hinclude.js with the `admin--include` Stimulus controller for the previews of the backoffice indexes; `pumukit:check:assets` reports the `render_hinclude()` calls left in a project.
- Replace Select2 in the channel search of the live event form with the jQuery UI autocomplete already used by the series search.
- Load Chart.js and its date adapter from the importmap in the backoffice dashboard and remove its separate Chart.js 4.4.6 build.
- Answer the picture uploads with JSON only and remove the old iframe responses and the hidden banner URL form.
- Configure the default protocol of the URL form fields (live channel, person, link and external player), deprecated since Symfony 7.1.
- Upload thumbnails, banners, materials, live event images and tracks through the Uppy Dashboard; the upload actions answer JSON on request and adding a track to an object without type no longer fails.
- Update composer dependencies (gedmo/doctrine-extensions, php-cs-fixer, pagerfanta, knp-menu-bundle, twig extensions, etc.).
- Enable the optional features by default: playlists and places menus, live event series autocomplete, backoffice button in the simplified inbox, portal menu statistics, unpublished warning and recursive inbox file counts. See UPGRADE-6.0.md.
- Store the IP of each view truncated to its network and a daily visitor hash instead of the full IP.

#### Fixed

- The places and precincts page returning a 500 when the `PLACES` root tag does not exist: it now explains the reason, and `pumukit:init:repo tag` creates the tag.
- Statistics API endpoints (`/api/media/views*`, `/api/media/*/most_viewed`) failing with a 500 when called without `from_date` or `to_date`.
- Cloning a live event without URL or without live channel failing with a 500.
- Deleting a place that is in use answering with a 500 instead of a conflict.
- `/api/user/users` failing with a circular reference when serializing the user's person.
- Backoffice and inbox requests without required parameters or with unknown ids (wizard server upload, people autocomplete, track thumbnail, group resources, inbox folder, Paella configuration, external player, new multimedia object) answering with a 500 instead of a 400 or 404.
- Deprecated Doctrine auto-mapping in the magic URL routes of the portal and the players: the documents are now mapped explicitly by `secret`.
- Series and playlist lists of the backoffice failing with a 500 for the rest of the session after a search request without text.
- Series styles without a name breaking the series styles page; creating a style now requires a name.
- Backoffice requests without their required parameters (`ids`, parent tag) answering with a 500 instead of a 400.
- User filters of the backoffice failing in debug mode when the saved criteria lacked the permission profile or the origin.
- `pumukit_core.full_magic_url` was ignored and always behaved as enabled because of a broken parameter reference.
- Several Symfony 6 runtime issues in the backoffice menu, the web profiler and the pagination adapters.
- Security advisories in `guzzlehttp/guzzle`, `guzzlehttp/psr7` and `api-platform/core`.
- Media API endpoint failing on multimedia objects whose metadata was stored as a JSON array.
- Media API endpoint failing with a 500 on blocked (archived) multimedia objects: `Immutable::user()` now returns the user id as a string, as Doctrine ODM hydrates it.
- Media API endpoint failing with a 500 on multimedia objects with external media: `Storage::path()` and `Storage::url()` return null when the storage has no path or URL.
- Missing translations in the backoffice and the portal for English, Spanish, Galician, Catalan, Valencian and Basque, and labels translated twice by the backoffice form theme.
- Video playback returning a 404 for local tracks: they are now served through `/trackfile` instead of redirecting to the blocked `/storage` path.
- Image-type multimedia objects returning a 404 on the player: the image now goes through `track_url()` (i.e. `/trackfile`) like documents and video tracks, instead of the raw `/storage/downloads/...` URL.
- Already-developed images (JPG/PNG/…) being run through the `image_raw_broadcastable` profile and rendered with darktable's default development modules (washed-out / blown-out output). The default image profile for a target now picks `image_raw` only when the master is actually a camera RAW file (extensions in `ImageRawUtils`); otherwise it uses the generic `image` profile. Set `target_default_profiles.<TARGET>.image_raw` next to `image` in `encoder.yaml` to opt in.

#### Deprecated

- The previous REST endpoints under `/api/` (not `/api/v1/`): they answer with `Deprecation` and `Link` headers, every call is logged in `var/log/legacy_api.log`, and they will be removed in 7.0. See UPGRADE-6.0.md for their replacements.

#### Removed

- API Platform 2.7 (replaced by 4.x), removing the two GraphQL security advisories and their audit ignores.
- `sensio/framework-extra-bundle` (abandoned).
- `symfony/templating` (unused), Swiftmailer and `symfony/swiftmailer-bundle`.
- The v4→v5 data migration commands (`pumukit:upgrade:*`); 6.0 is reached from 5.1.x, which already runs the v5 schema.
- Vendored SIMILE Timeline 2.3.1 assets (~1 MB) under `NewAdminBundle/Resources/public/js/timeline_2.3.1/`.
- Bootstrap 3.4.1 (`bundles/pumukitcore/vendor/bootstrap/` and the portal stylesheet with its glyphicons), Bootstrap Material Design 0.2 with its ripples and the RobotoDraft font loaded from Google Fonts, and SnackbarJS (see UPGRADE-6.0.md for the replacements).
- Per-bundle copies of jQuery, Bootstrap, moment, jQuery UI and bootstrap-datetimepicker in `pumukitnewadmin`, `pumukitwebtv` and `pumukitstatsui` (see UPGRADE-6.0.md for the replacements).
- Unused fields from the encoder profile configuration schema (`format`, `codec`, `mime_type`, `bitrate`, `framerate`, `channels`, `app`, `rel_duration_size`, `rel_duration_trans`, `file_cfg`, `prescript`, and `streamserver.{name,type,host,description}`); these were declared but never read by the application. `validateProfilesDir()` now identifies the failing profile by its array key in its error message instead of the redundant `streamserver.name`.
- The metadata translation button of the backoffice i18n fields, which was never implemented: `TranslateController`, the `pumukitnewadmin_metadata_translate` route, the `pumukitnewadmin.translatecontroller` alias and the `pumukit_new_admin.metadata_translators` parameter.
- The `pumukitnewadmin_seriespic_index` and `pumukitnewadmin_mmspic_index` routes, which pointed to controller actions that do not exist.
- The unused `copy` routes of series, multimedia objects, series types, roles, people and live channels, with `AdminController::copyAction()` and the `cloneResource()` methods of `Person`, `Role` and `Live`.
- The previous statistics pages (`pumukit_stats_series_index`, `pumukit_stats_mmobj_index`, the `Search`, `MostViewed` and `Views` templates and the `stats-ui--stats` controller), replaced by the statistics panel.
- The publication activity bubble chart and the server-rendered widgets of the dashboard, with `StatsService::getMmobjActivityByDayHour()`, `getActivityWindowStart()` and the owner argument of `getGlobalStats()`.
- The `pumukitnewadmin_seriespic_show`, `pumukitnewadmin_mmspic_show`, `pumukitnewadmin_track_info` and `pumukitnewadmin_track_autocomplete` routes, which pointed to controller actions that do not exist.


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
