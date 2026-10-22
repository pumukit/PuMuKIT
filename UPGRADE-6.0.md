# UPGRADE FROM 5.x to 6.0

This document lists the **code-level changes** in PuMuKIT 6.0 that affect maintainers of
PuMuKIT bundles and of projects that customize the core (own documents, services,
controllers or overridden templates). The deployment steps (Docker images, MongoDB server
upgrade, indexes) are described in [doc/upgrade/from5.1to6.0.md](doc/upgrade/from5.1to6.0.md).

> PuMuKIT 6.0 targets **PHP 8.5** and **Symfony 7.4 LTS**. This document is completed as each
> step of the migration lands on the `6.0.x` branch.

## Bundles

- Require `"pumukit/pumukit": "^6.0"`. Bundles compatible with PuMuKIT 6.0 are maintained in a
  `6.0.x` branch and their first compatible release is tagged `v6.0.0`, regardless of the
  major version they had before.

## PHP 8.5

The core requires `"php": "^8.5"` and the Docker image is built from `php:8.5-fpm`. Code
running on top of the core must not use what PHP 8.4 and 8.5 deprecate. The cases found in
PuMuKIT code were:

- `fgetcsv()`, `str_getcsv()` and `fputcsv()` must receive the `$escape` argument
  explicitly. Pass `'\\'` to keep the previous behaviour.
- `curl_close()` and `ReflectionMethod::setAccessible()`/`ReflectionProperty::setAccessible()`
  have no effect and are deprecated: remove the calls.
- Implicitly nullable parameters (`Foo $foo = null`) must be declared as `?Foo $foo = null`.
- The `session.sid_length` and `session.sid_bits_per_character` INI settings are deprecated
  and `disable_classes` was removed.

## Symfony 7.4

The core runs on Symfony 7.4 LTS with DoctrineMongoDBBundle 5.6 (`doctrine/persistence` 4).
`doctrine/annotations` is no longer installed. Changes that affect bundles and custom code:

- Symfony 7 adds native return types to the methods you override. A missing or wider type
  is a fatal error, e.g. `Command::execute()` must return `int` (not `?int`), and
  `configure()`, `initialize()`, `Bundle::build()` and `CompilerPassInterface::process()`
  return `void`.
- Bundle extensions extend `Symfony\Component\DependencyInjection\Extension\Extension`
  instead of `Symfony\Component\HttpKernel\DependencyInjection\Extension`.
- Voters: `voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token,
  ?Vote $vote = null): bool`.
- Inject rate limiters as `Symfony\Component\RateLimiter\RateLimiterFactoryInterface`.
- `UserInterface::eraseCredentials()` is deprecated. If it is empty, mark it with
  `#[\Deprecated]`; if it clears sensitive data, exclude that data in `__serialize()`.
- `Assert\Url` constraints must set `requireTld` explicitly (its default changes in
  Symfony 8). The core uses `requireTld: false` for the streaming URLs of live channels.
- Configuration nodes cannot be both required and have a default value.
- The dev routes of the profiler and the error preview import the `.php` files
  (`errors.php`, `wdt.php`, `profiler.php`) instead of the `.xml` ones, and the
  `twig.exception_controller` option is gone.
- `Request::get()` is deprecated: read from `$request->attributes`, `$request->query` or
  `$request->request` explicitly. Use `->all('key')` for array values, because
  `InputBag::get()` throws a `BadRequestException` when the value is an array. In Twig,
  use `app.request.attributes.get('_route')` instead of `app.request.get('_route')`.

### Request parameters read from a single source

The core no longer uses `Request::get()`, which accepted every parameter from the route
attributes, the query string or the request body. Each parameter is now read from the place
where PuMuKIT sends it. External callers that relied on the old fallback must adapt:

- The public APIs (`/api/media/*`, recorded, stats, live) and the annotations API keep
  accepting parameters both in the query string and in the body, in that order.
- `broadcast_password` (password protected videos and series) is only read from the body
  of the POST request, not from the query string.
- The backoffice lists read `criteria`, `page`, `paginate` and `sorting` from the query
  string only, and `criteria` and `sorting` must be arrays (`criteria[title]=...`).
- The batch actions read `ids` from the query string (a JSON string or an array).
- The encoder CPU actions read `cpu_name`, `activate` and `deactivate` from the query string.
- The series and playlist picture update reads `url` from the request body.

## Doctrine annotations are no longer supported

The integration of Doctrine annotations is disabled (`framework.annotations.enabled: false`)
and Symfony 7 removes it. Everything that was configured with docblock annotations must use
PHP attributes:

- **Doctrine ODM mapping** (`@MongoDB\Document`, `@MongoDB\Field`, ...): use the same classes
  as attributes, e.g. `#[MongoDB\Document]`. The mapping driver is detected automatically.
- **Gedmo** (`@Gedmo\Tree`, `@Gedmo\TreePath`, ...), **validation constraints**
  (`@Assert\...`) and **JMS Serializer** (`@Serializer\Exclude`, ...): same, as attributes.
- **Routes**: use `#[Route]` from `Symfony\Component\Routing\Attribute\Route` and load
  controllers with `type: attribute` instead of `type: annotation`.

A bundle or project that still maps documents with annotations makes the container fail with:

```
The service "doctrine_mongodb.odm.default_annotation_metadata_driver" has a dependency on a
non-existent service "annotation_reader".
```

Rector converts all of the above:

```php
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([__DIR__.'/Document'])
    ->withAttributesSets(symfony: true, mongoDb: true, gedmo: true, jms: true);
```

Review **traits** after the conversion. In a docblock annotation, `Foo::class` was resolved
with the imports of the class using the trait; in an attribute it is resolved in the
namespace of the trait itself. In the trait `Acme\Document\Traits\Pic`, the annotation
`@MongoDB\EmbedMany(targetDocument=Pic::class)` pointed to `Acme\Document\Pic`, but Rector
turns it into an attribute that points to `Acme\Document\Traits\Pic`. Import the document
and reference it explicitly:

```php
namespace Acme\Document\Traits;

use Acme\Document\Pic as DocumentPic;

trait Pic
{
    #[MongoDB\EmbedMany(targetDocument: DocumentPic::class)]
    private $pics;
}
```

## Backoffice menu builder

`Pumukit\NewAdminBundle\Menu\Builder` is now a service with constructor injection instead of a
container-aware class (`ContainerAwareInterface` and `ContainerAwareTrait` are removed in
Symfony 7):

- The menu is registered as `pumukit_newadmin_main`. The previous name
  `PumukitNewAdminBundle:Builder:mainMenu` is kept as an alias, so overridden
  `menu.html.twig` templates keep working; new templates should use
  `knp_menu_render('pumukit_newadmin_main', ...)`.
- `mainMenu()` now has the signature `mainMenu(array $options = [])`; the menu factory is
  injected in the constructor.
- `$this->container` is no longer available. Classes extending `Builder` must be registered
  as services, pass the parent constructor arguments and use the injected dependencies
  (`$this->parameters`, `$this->requestStack`, `$this->authorizationChecker`,
  `$this->menuItems`, `$this->blockItems`).

## Twig

The `spaceless` filter is deprecated since Twig 3.12. Core templates use the new
`pumukit_spaceless` filter, which produces exactly the same output. Overridden templates
should replace `{% apply spaceless %}` with `{% apply pumukit_spaceless %}`.

## Doctrine event listeners

The Gedmo `TreeListener` and `SortableListener` are registered as ODM event listeners
(`doctrine_mongodb.odm.event_listener`, one tag per event) with the Gedmo
`AttributeReader`, instead of event subscribers with the annotation reader. Projects that
redefine these services must do the same.

## Symfony 6.4 API changes

Changes found while porting bundles to PuMuKIT 6.0:

- `Symfony\Component\Security\Core\Security` is deprecated: use
  `Symfony\Bundle\SecurityBundle\Security` for the helper and
  `Symfony\Component\Security\Http\SecurityRequestAttributes` for the `LAST_USERNAME`,
  `AUTHENTICATION_ERROR` and `ACCESS_DENIED_ERROR` constants.
- `UsernamePasswordToken` takes `($user, $firewallName, $roles)`; the credentials argument
  was removed and the user must be a `UserInterface`.
- `AbstractController::get()` was removed: inject the services, and use
  `$request->getSession()` instead of `$this->get('session')`.
- `SensioFrameworkExtraBundle` is no longer installed: replace `@Route`, `@Security`,
  `@IsGranted` and `@Template` with the Symfony attributes (`#[Route]`, `#[IsGranted]`,
  `#[Template]`), and `@ParamConverter` with argument value resolvers.
- A service `bind` that no argument uses is an error. Remove the bind when the argument
  goes away, and add it back when a constructor starts using it again.
- Projects with their own kernel must use the `.container.dumper.inline_class_loader`
  parameter instead of `container.dumper.inline_class_loader`.
