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

#### 1.2 Register the new bundle as the "parent" of the Pumukit bundle:


```php
#PumukitExampleOrgWebTVBundle.php
<?php
namespace Pumukit\ExampleOrg\WebTVBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class PumukitExampleOrgWebTVBundle extends Bundle
{
  public function getParent()
  {
    return 'PumukitWebTVBundle';
  }
}
```

For more info see: http://symfony.com/doc/current/cookbook/bundles/inheritance.html

#### 1.3 Install the new bundle (if necessary).
`
$ php app/console  pumukit:install:bundle Pumukit/ExampleOrg/WebTVBundle/PumukitExampleOrgWebTVBundle
`
### 2.- Create your custom CSS rules

#### 2.1 Override 'custom.css.twig'
Override the `src/Pumukit/WebTVBundle/Resources/views/custom.css.twig`

`
$ touch src/Pumukit/ExampleOrg/WebTVBundle/Resources/views/custom.css.twig
`

```twig
{# src/Pumukit/ExampleOrg/WebTVBundle/Resources/views/custom.css.twig #}
{% extends 'PumukitWebTVBundle::default.css.twig' %}

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
Override the `Pumukit/ExampleOrg/WebTVBundle/Resources/views/logo.html.twig` template.

```html
<img src="{{ asset('bundles/pumukitwebtv/images/webtv/logo80px.png') }}" class="img-responsive" style="max-height:100%">
```


### 5.- Header (advanced)

Add your HTML on `src/Pumukit/ExampleOrg/WebTVBundle/Resources/views/Layout/header.html.twig` and its CSS in the base css file.

```html
<div>
  <!-- TOPHEADER -->
</div>

{% embed 'PumukitWebTVBundle:Layout:baseheader.html.twig' %}
  {% block logo_url %}
     <img src="{{ asset('bundles/pumukitwebtv/images/logo.png') }}" class="img-responsive">
  {% endblock %}
{% endembed %}

<div>
  <!-- OTHERHEADER -->
</div>

```
### 6.- Parameters:
Simply add the following sentences to your parameters.yml file to change the default values.

#### 6.1 Number of columns
The number of columns for almost every multimedia object and series listing.
```yaml
    columns_objs_bytag:        3             # Number of columns for bytag.  (Default 2)
    columns_objs_search:       3             # Number of columns for search. (Default 2)
    columns_objs_announces:    3             # Number of columns for announces. (Default 1);
    columns_objs_catalogue:    2             # Number of columns for full catalogue. (Default 1)
```

#### 6.2 Objects per page
The number of objects per page in the templates using the pager.
```yaml
    limit_objs_bytag:          3             # ByTag Pager limit.   (Default 10)
    limit_objs_search:         6             # Search Pager limit.  (Default 10)
    limit_objs_mostviewed:     6             # Mostviewed limit.     (Default 3)
    limit_objs_recentlyadded:  4             # Recentlyadded limit.    (Default 3)
```

#### 6.3 Menu Statistics
The statistics viewed at the bottom of the lateral menu.
```yaml
    menu_stats:                true          # To show stats on the menu or not. (Default true)
```

#### 6.4 Misc
Other values
```yaml
    catalogue_thumbnails:      true          # If set to true, the full catalogue will list thumbnails instead of text.
    menu_stats:                true          # To show stats on the menu or not. (Default true)
    categories_tag_cod:        UNESCO       # Cod of Root Tag to create the Categories page.
```
