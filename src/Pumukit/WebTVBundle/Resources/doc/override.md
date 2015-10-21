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
{#s rc/Pumukit/ExampleOrg/WebTVBundle/Resources/views/custom.css.twig #}
{% extends 'PumukitWebTVBundle::default.css.twig' %}

{% block body %}

{# HERE you can set your variables to be overrided #}
{# Examples: #}

{% set content_max_width = "1200px" %}
{% set font_base_color = "#131" %}

{{ parent() }}
{% endblock %}
```
#### List of available variables:

##### Most common
```twig
a_link_font_color                 default("#337ab7")  {# css rule  for a links #}
a_link_selected_font_color        default("#23527c")  {# css rule  for selected a links #}
breadcrumbs_background            default("#ed1556")  {# css rule  for a breadcrumbs background  #}
breadcrumbs_font_color            default("white")    {# css rule  for a breadcrumbs font color  #}
font_base_color                   default("#000")     {# css rule  for base font color  #}
header_background                 default("#fff")     {# css rule  for header background  #}
menu_background                   default("#004361")  {# css rule  for menu background  #}
menu_font_color                   default("#fff")     {# css rule for menu font color  #}
page_background                   default("#ddd")     {# css rule for body background  #}
```
##### All
```
a_link_font_color                 default("#337ab7")
a_link_selected_font_color        default("#23527c")  
breadcrumbs_background            default("#ed1556")  
breadcrumbs_font_color            default("white")  
breadcrumbs_separator             default('»')  
content_background                default("#fff")  
content_max_width                 default("1400px")  
font_base_color                   default("#000")  
header_background                 default("#fff")  
menu_background                   default("#004361")  
menu_font_color                   default("#fff")  
menu_padding                      default("10px 10px 10px 20px")  
menu_selected_background          default("#888")  
page_background                   default("#ddd")  

breadcrumbs_back_background       default(breadcrumbs_background)  
breadcrumbs_max_width             default(content_max_width)  
header_max_width                  default(content_max_width)  
```
### 3.- Change the footer
Add your HTML on `src/Pumukit/ExampleOrg/WebTVBundle/Resources/views/Layout/footer.html.twig` and its CSS in the base css file.


### 4.- Logo
Override the `Pumukit/ExampleOrg/WebTVBundle/Resources/views/layout.html.twig` template.

```html
{% extends 'PumukitWebTVBundle:Layout:baseheader.html.twig' %}

{% block logo_url %}
    <img src="{{ asset('bundles/pumukitexampleorgwebtv/images/logo.png') }}" class="img-responsive">
{% endblock %}
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
