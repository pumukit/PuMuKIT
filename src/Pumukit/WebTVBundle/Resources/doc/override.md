Override manual
===============

Override the PumukitWebTvBundle allow you to change:

* CSS base
* Footer
* Logo
* Header (advanced)



Process
--------

### 1.- Create new webtv bundle.

#### 1.1 Generate bundle.

`
$ php app/console  generate:bundle --namespace=Pumukit/Teltek/WebTVBundle --dir=src --no-interaction
`

#### 1.2 Registering the new bundle as the "parent" of Pumukit bundle:


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

More info see: http://symfony.com/doc/current/cookbook/bundles/inheritance.html

#### 1.3 Install the new bundle (if necesary).
`
$ php app/console  pumukit:install:bundle Pumukit/Teltek/WebTVBundle/PumukitTeltekWebTVBundle
`
### 2.- Create a custom CSS

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
Add your HTML on `src/Pumukit/Teltek/WebTVBundle/Resources/views/Layout/footer.html.twig` and the CSS in the base css file.


### 4.- logo_url
Override the `Pumukit/Teltek/WebTVBundle/Resources/views/layout.html.twig` template.

```html
{% extends 'PumukitWebTVBundle:Layout:baseheader.html.twig' %}

{% block logo_url %}
    <img src="{{ asset('bundles/pumukitteltekwebtv/images/logo.png') }}" class="img-responsive">
{% endblock %}
```


### 5.- Header (advanced)

Add your HTML on `src/Pumukit/Teltek/WebTVBundle/Resources/views/Layout/header.html.twig` and the CSS in the base css file.

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




