# CHANGELOG

Web version of the changelog in http://pumukit.org
To get the diff for a specific change, go to https://github.com/pumukit/PuMuKIT/commit/XXX where XXX is the change hash
To get the diff between two versions, go to https://github.com/pumukit/PuMuKIT/compare/3.0.0...3.1.x

## [3.0.0](https://github.com/campusdomar/PuMuKIT2/compare/3.0.0...2.6.0) (2019-06-10)
- Update to PHP technology stack (PHP7, Symfony 3.4...)
- Bugfixing.

## [2.6.0](https://github.com/campusdomar/PuMuKIT2/compare/2.6.0...2.5.0) (2019-04-02)
- Added custom columns configuration at UNESCO catalogue.
- Bugfixing.

## [2.5.0](https://github.com/campusdomar/PuMuKIT2/compare/2.5.0...2.4.0) (2019-01-10)
- Added a button to synchronize metadata from one to the rest of the MMO of a Series.
- Enabled Series synchonization between PuMuKIT and Opencast. Now they can be selected on Galicaster Units.
- Created a shortcut from the MMO player page to edit it.
- Automatic importation from Opencast to PuMuKIT.
- Original files names stored in PuMuKIT.
- Copy of keywords enabled.
- Added search by groups at UNESCO catalogue.
- Live events poster edition is allowed.
- New chat tool within live events.
- Live events messages customization added.
- HTML tags compatibility for live events messages.
- Added a button on live events to copy the event's title and description to its Series.
- Play VOD playlist at an ended live event iframe.
- Added new Series comment input.
- Added simplified ID catalogation to Series and MMO.
- Delete of Opencast tracks on cloned MMO allowed.
- Bugfixing and performance improvements.

## [2.4.0](https://github.com/campusdomar/PuMuKIT2/compare/2.3.3...2.4.0) (2018-02-06)
- Designed a new MMO view to ease UNESCO tags cataloging including an advanced MMO searching tool.
- Created a new advanced live events feature.
- Support for multimedia objects with an external player.
- MMO tracks can be downloaded from the web portal.
- HTML5 thumbnail extractor for MMO.
- Searching text indexes improvements.
- Fixed WCAG2AA accessibility (a11y) errors.
- Added a parameter to hide Series.
- Created new sorting methods for MMO inside a Series.
- Custom CSS can be created and used within a Series.
- Added a field to add comments for MMO.
- Bugfixing and performance improvements.

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
