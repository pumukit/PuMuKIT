WizardBundle configuration
==================================

Configuration:

```
pumukit_wizard:
    show_license: false
    license_dir: ''
    show_tags: false
    tag_parent_code: 'UNESCO'
    show_object_license:  false
    mandatory_title: false
    reuse_series: false
    reuse_admin_series: false
    show_simple_mm_title: false
    show_simple_series_title: false
    simple_default_master_profile:
```

* `show_license` Enable showing license in first step
* `license_dir` Path dir of the license files to show in first step if enabled according to locale. E.g.: '%kernel.project_dir%/src/Pumukit/WizardBundle/Resources/data/license/'. In this folder there should be files named after its locale language: es.txt, en.txt, fr.txt, etc.
* `show_tags` Enable adding tag to a MultimediaObject in metadata step
* `tag_parent_code` Parent tag code of tags available to add to a Multimedia Object. E.g.: UNESCO
* `show_object_license` Enable adding license to a MultimediaObject in metadata step. This license is defined in pumukit_schema.license (could be a string or an array).
* `mandatory_title` Enable to force mandatory title in Series and Multimedia Object steps.
* `reuse_series` Enable adding new multimedia object to an existing series belonging to the logged in user.
* `reuse_admin_series` Only valid when parameter reuse_series is set to True. If reuse_admin_series is true, the admin user can reuse only the series he/she created. If reuse_admin_series is set to false, the admin user can reuse any series of PuMuKIT.
* `show_simple_mm_title` Enable showing Multimedia Object title in Simple Wizard form.
* `show_simple_series_title` Enable showing Series title in Simple Wizard form.
* `simple_default_master_profile` Force a default master profile for multimedia objects created using the simple wizard (used by Moodle or by OpenEdx)
