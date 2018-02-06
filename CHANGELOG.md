# CHANGELOG

Web version of the changelog in http://pumukit.org/pmk-2-x-release-archive/
To get the diff for a specific change, go to https://github.com/campusdomar/PuMuKIT2/commit/XXX where XXX is the change hash
To get the diff between two versions, go to https://github.com/campusdomar/PuMuKIT2/compare/2.0.0...2.1.0-rc1


## [2.4.0](https://github.com/campusdomar/PuMuKIT2/compare/2.3.3...2.4.0) (2018-02-06)
- Support for multimedia objects with an external player.
- Fix WCAG2AA accessibility (a11y) errors.
- HTML5 thumbnail generator
- LiveBundle: Define live_type default value as WOWZA and not FMS.
- EncoderBundle: Add 'downloadable' parameter to Profiles with default value as false.
- NewAdminBundle: Add new paramaters advance_live_event, advance_live_event_create_serie_pic and advance_live_event_create_default_pic.
- SchemaBundle: Add new Permission ROLE_ACCESS_SERIES_STYLE.
- SchemaBundle: Add new Document and Repository for SeriesStyle.
- SchemaBundle: SeriesDocument - Pic is defined as Traits.
- SchemaBundle: SeriesDocument - Add new field 'sorting'. Default value as MANUAL.
- SchemaBundle: SeriesDocument - Add new field 'hide'. Default value as true.
- SchemaBundle: MultimediaObjectDocument - Add EmbeddedEvent.
- SchemaBundle: MultimediaObjectDocument - Add EmbeddedSocial.
- SchemaBundle: MultimediaObjectDocument - Pic, Link & Material are defined as Traits.
- SchemaBundle: MultimediaObjectDocument - Add new field 'type'. Default value as UNKNOWN.
- SchemaBundle: MultimediaObjectDocument - Add new field 'islive'. Default value as false.
- SchemaBundle: MultimediaObjectDocument - Add new field 'comments'. No default value.
- SchemaBundle: MultimediaObjectDocument - Use of new property 'externalplayer'.
- SchemaBundle: TrackDocument - Add new field 'allowDownload'. Default value as false. Defined by the profile of the encoder bundle.

## [2.3.4] (YYYY-MM-DD) Unreleased
- Bug fixes related to broadcastable_master ([BC note in encoder.yml](https://github.com/campusdomar/PuMuKIT2/commit/5ade04b001ae300646a8e9c810bc2e72e))

## [2.3.3](https://github.com/campusdomar/PuMuKIT2/compare/2.3.2...2.3.3) (2017-10-19)
- Bug fixes.

## [2.3.2](https://github.com/campusdomar/PuMuKIT2/compare/2.3.1...2.3.2) (2017-09-15)
- Configurable imported tag opencast.

## [2.3.1](https://github.com/campusdomar/PuMuKIT2/compare/2.3.0...2.3.1) (2017-09-13)
- Update default permission profiles.
- Promote viewer user on LDAP integration.
- Add accessibility.
- Bug fixes.

## [2.3.0][2.3.0] (2017-06-09)
- Advanced MOODLE integration.
- Micro-Site Support. Create sub-portals (college, library, etc..) in your PuMuKIT video portal
- Authorization based on User Groups.
- Opencast 2.x integration (including OC v2.3)
- OAuth 2 support.
- Direct LDAP Support (Without CAS integration).
- New "All Multimedia Objects-UI". New back-office UI where you can manage all your videos from all your series in the same UI)
- Broadcast-ready Master track (file) in MOs. Same file as Master and broadcast-copy for storage optimization.
- Improvements to the documentation and minor bug fixing.
- Improved documentation and fixed minor bugs .

## [2.2.0][2.2.0] (2016-04-28)
- Added a responsive WebTV portal bundle. The old not responsive web portal is maintained as legacy to not break the compatibility.
- Added PumukitStatsUI bundle as default. (Adds statistics of series and multimedia objects to the back-office)
- Added 'personal scope' support for auto-publishing to the back-office.
- Added 'Permission Profiles' to the back-office.
- Improved CAS support.
- Added new LDAP broadcast.
- Added support to switch default portal player.
- Improved documentation and fixed minor bugs.

## [2.1.1][2.1.1] (2016-04-28)
- Improve performance.
- Bug fixes.

## [2.1.0][2.1.0] (2015-11-16)
- Added migration path from PuMuKIT1.7 to PuMuKIT2
- Removed MoodleBundle out of the project to be used as a third party bundle.
- Production version
- Bootstrap based Material design AdminUI

## 2.0.0 (2015-02-12)
- Initial concept of technologies


[Unreleased]:https://github.com/campusdomar/PuMuKIT2/compare/2.3.0...HEAD
[2.1.0]:https://github.com/campusdomar/PuMuKIT2/compare/2.0.0...2.1.0
[2.1.1]:https://github.com/campusdomar/PuMuKIT2/compare/2.1.0...2.1.1
[2.2.0]:https://github.com/campusdomar/PuMuKIT2/compare/2.1.1...2.2.0
[2.3.0]:https://github.com/campusdomar/PuMuKIT2/compare/2.2.0...2.3.0
