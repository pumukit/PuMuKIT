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
$ php app/console  generate:bundle --namespace=Pumukit/Teltek/WebTVBundle --dir=src --no-interaction
`

#### 1.2 Register the new bundle as the "parent" of the Pumukit bundle:


```php
#PumukitTeltekWebTVBundle.php
<?php

namespace Pumukit\Teltek\WebTVBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class PumukitTeltekWebTVBundle extends Bundle
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
$ php app/console  pumukit:install:bundle Pumukit/Teltek/WebTVBundle/PumukitTeltekWebTVBundle
`
### 2.- Create your custom CSS rules

#### 2.1 Create base css file

```
$ mkdir -p src/Pumukit/Teltek/WebTVBundle/Resources/public/{css,images,js}
$ cp src/Pumukit/WebTVBundle/Resources/public/css/cies.css src/Pumukit/Teltek/WebTVBundle/Resources/public/css/
$ php app/console assets:install web --symlink
```


#### 2.1 Load CSS in the layout
Override the `src/Pumukit/Teltek/WebTVBundle/Resources/views/layout.html.twig` template:

```html
{% extends 'PumukitWebTVBundle:Layout:base.html.twig' %}

{% block stylesheets %}
  {{ parent() }}
  <link href="{{ asset('bundles/pumukitteltekwebtv/css/cies.css') }}" type="text/css" rel="stylesheet" media="screen"/>
{% endblock %}
```


### 3.- Change the footer
Add your HTML on `src/Pumukit/Teltek/WebTVBundle/Resources/views/Layout/footer.html.twig` and its CSS in the base css file.


### 4.- Logo
Override the `Pumukit/Teltek/WebTVBundle/Resources/views/layout.html.twig` template.

```html
{% extends 'PumukitWebTVBundle:Layout:baseheader.html.twig' %}

{% block logo_url %}
    <img src="{{ asset('bundles/pumukitteltekwebtv/images/logo.png') }}" class="img-responsive">
{% endblock %}
```


### 5.- Header (advanced)

Add your HTML on `src/Pumukit/Teltek/WebTVBundle/Resources/views/Layout/header.html.twig` and its CSS in the base css file.

```html
<div>
  <!-- TOPHEADER -->
</div>

{% embed 'PumukitWebTVBundle:Layout:baseheader.html.twig' %}
  {% block logo_url %}
     <img src="{{ asset('bundles/pumukitwebtv/images/logo_cies.png') }}" class="img-responsive">
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


