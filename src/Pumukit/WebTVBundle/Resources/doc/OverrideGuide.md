Override manual
===============

Overriding the PumukitWebTvBundle allows you to change:

* Base CSS
* Footer
* Logo
* Header (advanced)
* Number of columns for the Latest uploads, Search and ByTag templates
* Number of objects per page on the Search, Most viewed, Recently added and ByTag templates
* Stats widget (added by default)



Process
--------

### 1.- Create new WebTV bundle.

#### 1.1 Generate the bundle.

`
$ php app/console  generate:bundle --namespace=Pumukit/ExampleOrg/WebTVBundle --dir=src --no-interaction
`

#### 1.2 Override bundle with service configuration (services.xml):


```xml
<service id="pumukitcore.twig_loader.customwebtv_bundle" class="Pumukit\CoreBundle\Services\TwigTemplateLoaderService">
    <argument type="service" id="templating.locator" />
    <argument type="service" id="templating.name_parser" />
    <argument>%kernel.project_dir%</argument>
    <argument>WebTVBundle</argument>
    <argument>{customName}WebTVBundle</argument>
    <tag name="twig.loader" priority="1"/>
</service>
```

For more info see: http://symfony.com/doc/current/cookbook/bundles/inheritance.html

### 2.- Create your custom CSS rules

#### 2.1 Override 'custom.css.twig'
Override the `src/Pumukit/WebTVBundle/Resources/views/custom.css.twig`

`
$ touch src/Pumukit/ExampleOrg/WebTVBundle/Resources/views/custom.css.twig
`

```twig
{# src/Pumukit/ExampleOrg/WebTVBundle/Resources/views/custom.css.twig #}
{% extends 'PumukitWebTVBundle' %}

{% block body %}

{# HERE you can set your variables to be overrided #}
{# Examples: #}

{% set content_max_width = "1200px" %}
{% set font_base_color = "#131" %}

{{ parent() }}
{# Add your own CSS rules #}
{% endblock %}
```
#### List of available variables:

##### Basic ones
```twig
default_contrast_background              {# Breadcrumbs and a few other details #}
default_light_background                 {# Slidebar and others background #}
default_content_background               {# content background #}

default_contrast_font                    {# Font color for the contrast background #}
default_light_font                       {# Font color for the light background #}
default_content_font                     {# Font color for the main content #}  
default_content_link_font                {# Font color for links in the main content #}
default_content_link_selected_font       {# Font color for selected links in the main content #}

breadcrumbs_separator                    {# String to use as separator for breadcrumbs #}
content_max_width                        {# Max width of your webpage content #}

menu_padding                             {# Customized menu padding #}
page_background                          {# Background for the body (behind the content) #}
```
##### Other rules
```
breadcrumbs_background            default(default_contrast_background)
breadcrumbs_font_color            default(default_contrast_font)
breadcrumbs_back_background       default(breadcrumbs_background)
breadcrumbs_max_width             default(content_max_width)
content_background                default(default_content_background)
header_max_width                  default(content_max_width)
header_background                 default(default_light_background)
label_background                  default(default_contrast_background)
label_font                        default(default_contrast_font)
menu_background                   default(default_light_background)
menu_font_color                   default(default_light_font)
menu_selected_background          default(default_light_font)
menu_selected_font_color          default(default_light_background)
mmobj_font_color                  default(default_content_font)
mmobj_selected_font_color         default(default_content_font)
mmobj_selected_background         default(page_background)
mmobj_serie_background            default(mmobj_selected_background)
mmobj_serie_font                  default(mmobj_selected_font_color)
navbar_background                 default(default_light_background)
navbar_font                       default(default_light_font)
panel_default_background          default(default_light_background)
panel_default_font                default(default_light_font)

```
### 3.- Change the footer
Add your HTML on `src/Pumukit/ExampleOrg/WebTVBundle/Resources/views/Layout/footer.html.twig` and its CSS in the base css file.


### 4.- Logo
Override the `Pumukit/ExampleOrg/WebTVBundle/Resources/views/Layout/logo.html.twig` template.

```html
<img src="{{ asset('bundles/pumukitwebtv/images/webtv/logo80px.png') }}" class="img-responsive" style="max-height:100%" alt="{% trans %}Logo{% endtrans %}"/>
```


### 5.- Header (advanced)

Add your HTML on `src/Pumukit/ExampleOrg/WebTVBundle/Resources/views/Layout/header.html.twig` and its CSS in the base css file.

```html
<div>
  <!-- TOPHEADER -->
</div>

{% embed 'PumukitWebTVBundle:Layout:baseheader.html.twig' %}
  {% block logo_url %}
     <img src="{{ asset('bundles/pumukitwebtv/images/logo.png') }}" class="img-responsive" alt="{% trans %}Logo{% endtrans %}"/>
  {% endblock %}
{% endembed %}

<div>
  <!-- OTHERHEADER -->
</div>

```
### 6.- Parameters:

To override the parameter rules first you must edit the "DependencyInjection" file on your bundle:

``src/Pumukit/ExampleOrg/WebTVBundle/DependencyInjection/PumukitExampleOrgWebTVExtension.php``

```php
...
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('parameters.yml');
    }
    ...
```


Below is the full parameter rules and their explanation:

```yml
 parameters:
  breadcrumbs_home_title:    'WebTV'                # 'Home' option title for the breadcrumbs service.
  catalogue_thumbnails:      true                   # If set to true, the full catalogue will list thumbnails instead of text.
  categories_tag_cod:        ITUNESU                # Cod of Root Tag to create the Categories page.
  categories.list_general_tags:  true               # If true, adds a 'general tag' to each category.
  columns_objs_announces:    2                      # Number of columns for announces. (Default 1)
  columns_objs_bytag:        2                      # Number of columns for bytag.  (Default 2)
  columns_objs_catalogue:    2                      # Number of columns for full catalogue. (Default 1)
  columns_objs_search:       2                      # Number of columns for search. (Default 2)
  limit_objs_bytag:          10                     # ByTag Pager limit.   (Default 10)
  limit_objs_mostviewed:     3                      # Mostviewed limit.     (Default 3)
  limit_objs_recentlyadded:  3                      # Recentlyadded limit.    (Default 3)
  limit_objs_search:         10                     # Search Pager limit.  (Default 10)
  limit_objs_series:         10                     # Search Pager limit.  (Default 10)
  limit_objs_player_series:  10                     # Limit for mmobjs to appear on the mmobj player (Default 10)
  search.parent_tag.cod:     ITUNESU                # Search controller option for the main tag search.
  search.parent_tag_2.cod:   null                   # Search controller option for the optional tag search.
  menu.announces_title:      'Latest Uploads'       # 'Announces' option title for the menu widget.
  menu.categories_title:     'By subject catalogue' # 'Categories' option title for the menu widget.
  menu.home_title:           'Home'                 # 'Home' option title for the menu widget.
  menu.mediateca_title:      'Full Catalogue'       # 'Mediateca' option title for the menu widget.
  menu.show_stats:           false                  # To show stats on the menu or not. (Default true)
  menu.search_title:         'Search'               # 'Search' option title for the menu widget.

  pumukit_web_tv.breadcrumbs_all_title: 'All'
  pumukit_web_tv.breadcrumbs_all_route: 'pumukit_webtv_medialibrary_index'

  pumukit_web_tv.breadcrumbs_parentweb:             # 'If set to an array, a 'parent' will always appear as first element in the breadcrumbs service.
    title:  'Pumukit University'
    url:    'http://www.pumukit.org'

  pumukit_web_tv.default_pic: '/bundles/pumukitwebtv/images/no_pic.jpg'
  pumukit_web_tv.linktagtosearch: false             # If set to true, the links to tags will link to a search template with the tag already selected on the search.

  pumukit_web_tv.media_library.filter_tags:
    - DIRECTRIZ
    - UNESCO

  pumukit.intro:            null                   # If set to an url, plays that url before every video.

  pumukit_web_tv.primary_color:   '#d66400'         # Primary color. Must be the same as in breadcrumbs_back_background in default.css.twig
  pumukit_web_tv.secondary_color: '#fff'            # Secondary color. Must be the same as in header_background in default.css.twig
```
