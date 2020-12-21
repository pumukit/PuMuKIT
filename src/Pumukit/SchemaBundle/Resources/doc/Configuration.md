SchemaBundle configuration
==========================

Configuration:

```
pumukit_schema:
    send_email_on_user_added_as_owner: false
    user_can_reject_owner_of_multimedia_object: true
    multimedia_object_add_owner_subject: 'Email subject on event add owner to multimedia object'
    multimedia_object_add_owner_template: 'PumukitNewAdminBundle:MultimediaObject:Owner/email.html.twig'
    default_copyright: ''
    default_license: ''
    default_series_pic: /bundles/pumukitschema/images/series_folder.png
    default_playlist_pic: /bundles/pumukitschema/images/playlist_folder.png
    default_video_pic: /bundles/pumukitschema/images/video_none.jpg
    default_audio_hd_pic: /bundles/pumukitschema/images/audio_hd.svg
    default_audio_sd_pic: /bundles/pumukitschema/images/audio_sd.svg
    enable_add_user_as_person: true
    personal_scope_role_code: owner
    personal_scope_delete_owners: false
    gen_user_salt: true
```

* `send_email_on_user_added_as_owner` Send email to added user as owner on multimedia object
* `user_can_reject_owner_of_multimedia_object` On user added as owner email received, the user can reject the association
* `multimedia_object_add_owner_subject` Subject of email sent on added user as owner
* `multimedia_object_add_owner_template` Template of email sent on added user as owner
* `default_copyright` Default copyright MultimediaObject
* `default_license` Default license MultimediaObject
* `default_series_pic` Default Series picture
* `default_playlist_pic` Default Playlist picture
* `default_video_pic` Default video picture
* `default_audio_hd_pic` Default audio HD picture
* `default_audio_sd_pic` Default audio SD picture
* `enable_add_user_as_person` Add logged in User as Person to MultimediaObjects
* `personal_scope_role_code` Role code related to Personal Scope User to use as EmbeddedPerson
* `personal_scope_delete_owners` Allow Personal Scope users to delete other owners of Series and MultimediaObjects
* `gen_user_salt` Disable the generation of a random user salt. Required to use PuMuKIT as a CAS user provider. 
