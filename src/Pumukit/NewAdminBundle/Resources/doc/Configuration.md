NewAdminBundle configuration
============================

Configuration:

```
pumukit_new_admin:
    disable_broadcast_creation: true
    show_menu_place_and_precinct: false
    multimedia_object_label: Multimedia Objects
    show_naked_pub_tab: false
    licenses:
        - Copyright (Licencia propietaria)
        - Reconocimiento-NoComercial-CompartirIgual 3.0 España (CC BY-NC-SA 3.0 ES)
        - Reconocimiento 4.0 Internacional (CC BY 4.0)
        - Reconocimiento-SinObraDerivada 3.0 España (CC BY-ND 3.0 ES)
        - Reconocimiento-NoComercial-SinObraDerivada 3.0 España (CC BY-NC-ND 3.0 ES)
        - Reconocimiento-CompartirIgual 2.5 España (CC BY-SA 2.5 ES)
        - Reconocimiento-NoComercial 3.0 España (CC BY-NC 3.0 ES)
        - Dominio público (PD)
        - Creative Commons Legal Code CC0 1.0 Universal
    base_catalogue_tag: null
```

* `disable_broadcast_creation` @Deprecated - Disable the creation of new Broadcasts
* `show_menu_place_and_precinct` Show separated menu places and precinct
* `multimedia_object_label` Name of the label of the list of Multimedia Objects in Menu Builder and in the title of the page
* `show_naked_pub_tab` if true, it shows a simplified publication tab on the naked view
* `licenses` List of licenses used for series and multimedia objects. Text input used if empty
* `base_catalogue_tag` Code of the tag to use on catalogue
