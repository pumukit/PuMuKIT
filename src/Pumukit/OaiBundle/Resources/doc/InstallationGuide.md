Installation Guide
==================

Steps to install and configure this bundle:

1.- Install the bundle into your Pumukit root project:

```bash
$ cd /path/to/pumukit/
$ php app/console pumukit:install:bundle Pumukit/OaiBundle/PumukitOaiBundle
```

2.- (Optional) Configure the bundle:

```
# Default configuration for extension with alias: "pumukit_oai"
pumukit_oai:

    # List only multimedia objects in published status
    list_only_published_objects:  true

    # The pub_channel_tag parameter used in the frontend filter
    pub_channel_tag:      PUCHWEBTV

    # The display_track_tag parameter used in the frontend filter
    display_track_tag:    display

    # Use special tag dc:thumbnail to list the first object thumbnail (deprecated and non standard)
    use_dc_thumbnail:     true

    # DublinCore type for video contents. See http://dublincore.org/documents/dcmi-type-vocabulary/#H7
    video_dc_type:        'Moving Image'

    # DublinCore type for audio contents. See http://dublincore.org/documents/dcmi-type-vocabulary/#H7
    audio_dc_type:        Sound

    # PuMuKIT role used to get the dc:creator. Null to use all the roles
    role_for_dc_creator:  actor

    # Use the object license as dc:rights
    use_license_as_dc_rights:  false

    # Use the object copyright as dc:publisher
    use_copyright_as_dc_publisher:  false

    # Format used with dc:subject. All: "120000 - Mathematics", Code: "120000", Title: "Mathematics", E-ciencia: "12 Matemáticas"
    dc_subject_format:    ~ # One of "all"; "code"; "title"; "e-ciencia"

```