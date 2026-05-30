# Playbook: cambios de código Symfony 5.4 → 6.4

Recopilación de **todos los cambios de código** aplicados al core de PuMuKIT para subir a
Symfony 6.4. Úsalo como checklist al migrar cada bundle externo (`teltek/*`, CAS, SAML,
OAM, LDAP, etc.). Para cada punto: cómo localizarlo (grep) y el cambio before → after.

> Comandos pensados para ejecutarse dentro del bundle. Recuerda: composer/PHP corren en el
> contenedor (`make composer CMD="..."`, `make php-shell`).

---

## 0. composer.json del bundle

- [ ] Subir la restricción de Symfony. Si el bundle pinnea componentes core:
  - Opción recomendada (mismo esquema que el core): componentes core a `"*"` + en `extra`:
    ```json
    "extra": { "symfony": { "require": "6.4.*" } }
    ```
  - O bien constraint multi-rango si el bundle debe soportar varias: `"^5.4 || ^6.4"`.
- [ ] Bundles que NO siguen el versionado core, déjalos explícitos: `symfony/flex` `^2`,
      `symfony/monolog-bundle`, `symfony/maker-bundle`.
- [ ] Si el bundle requiere `pumukit/pumukit`, cuando se corte PuMuKIT 6.0 habrá que subir
      esa constraint a `^6.0` (coordinar el release).
- [ ] Quitar dependencias eliminadas en SF6 si las usa: `symfony/templating`,
      `sensio/framework-extra-bundle` (abandonado), `swiftmailer/swiftmailer` +
      `symfony/swiftmailer-bundle`, `symfony/security-guard`.
- [ ] **Aviso de dependencia compartida:** no se puede `composer remove` de un paquete
      abandonado (p.ej. `sensio/framework-extra-bundle`) mientras **otro bundle del proyecto
      aún lo use**. El core de PuMuKIT ya migró su código fuera de sensio, pero los bundles
      `teltek/*` siguen usando `@Security`/`@ParamConverter` → quitar el paquete rompe sus
      controladores (*"annotation … was never imported"*). Migrar el código de cada bundle a
      atributos PRIMERO; eliminar el paquete solo cuando ningún bundle lo use (release
      coordinado). Tip: `composer remove` dispara el recipe (re-añade `bundles.php` y config);
      si lo reviertes con `composer require`, revisa que `bundles.php`/config queden coherentes.
- [ ] **Pagerfanta v2 → v3**: al subir `babdev/pagerfanta-bundle` a `^3`, los adaptadores
      Doctrine pasan a ser **paquetes separados** que ya no se instalan solos. Si el bundle
      usa `Pagerfanta\Doctrine\MongoDBODM\QueryAdapter` o `Pagerfanta\Doctrine\Collections\CollectionAdapter`
      (error típico: *Attempted to load class "QueryAdapter" from namespace "Pagerfanta\Doctrine\MongoDBODM"*),
      añadir:
      ```bash
      composer require pagerfanta/doctrine-mongodb-odm-adapter:^3 pagerfanta/doctrine-collections-adapter:^3
      ```
      Los namespaces de las clases no cambian; solo hay que instalar los paquetes.
      Además, la **función Twig `pagerfanta()`** se movió a su propio paquete (error:
      *Unknown function "pagerfanta"* en una plantilla de paginación):
      ```bash
      composer require pagerfanta/twig:^3
      ```
- [ ] **Pagerfanta v4 — tipos estrictos:** `Pagerfanta::setMaxPerPage(int)` y `setCurrentPage(int)`
      ya **exigen `int`** (v3 era laxo). Los valores de `paginate`/`page` que vienen del query/sesión
      llegan como **string** → `TypeError: must be of type int, string given`. Castear en cada llamada:
      `->setMaxPerPage((int) $session->get(...))`, `->setCurrentPage((int) $page)`. Buscar:
      `grep -rn "setMaxPerPage(\|setCurrentPage(" src/`.

Buscar:
```bash
grep -nE '"symfony/|swiftmailer|templating|security-guard|framework-extra' composer.json
```

---

## 1. Sesión: SessionInterface autowired → RequestStack

`SessionInterface` ya no se puede inyectar (excluida del autowiring). Solo aplica a los
puntos **autowired** (constructores de servicios/controladores y args de acciones); los
type-hints en métodos que reciben la sesión por parámetro NO cambian.

Buscar:
```bash
grep -rn "SessionInterface" src/
```

Before → After (constructor):
```php
use Symfony\Component\HttpFoundation\Session\SessionInterface;   // quitar
use Symfony\Component\HttpFoundation\RequestStack;               // añadir

public function __construct(SessionInterface $session) { $this->session = $session; }
// →
public function __construct(RequestStack $requestStack) { $this->requestStack = $requestStack; }

$this->session->get('x');           // →  $this->requestStack->getSession()->get('x')
```

En **acciones de controlador** que reciben `SessionInterface $session`: quitar el parámetro
y usar `$request->getSession()` (el `Request $request` ya suele estar).

---

## 2. Eventos del kernel: isMasterRequest / getMasterRequest

Buscar:
```bash
grep -rn "isMasterRequest\|getMasterRequest" src/
```
- `RequestEvent::isMasterRequest()` → `isMainRequest()`
- `RequestStack::getMasterRequest()` → `getMainRequest()`

(Cuidado: `$track->isMaster()` de dominio NO se toca; es otro método.)

---

## 3. Voters tipados

`Symfony\...\Voter` ahora declara tipos. Buscar:
```bash
grep -rn "extends Voter" src/
```
Before → After:
```php
protected function supports($attribute, $subject)                              // →
protected function supports(string $attribute, $subject): bool

protected function voteOnAttribute($attribute, $subject, TokenInterface $token) // →
protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
```

---

## 4. Seguridad (si el bundle aporta auth/SSO) — Guard → nuevo authenticator

Guard se eliminó en SF6. Ver detalle y contrato de extensibilidad en
[[symfony_6.4]] (sección 1.3). Resumen:

Buscar:
```bash
grep -rn "Security\\\\Guard\|AbstractGuardAuthenticator\|AbstractFormLoginAuthenticator\|GuardAuthenticatorInterface" src/
```
- Authenticator: `AbstractFormLoginAuthenticator`/Guard → `AbstractLoginFormAuthenticator`
  (o `AbstractAuthenticator`) con `authenticate(): Passport`
  (`UserBadge` + `PasswordCredentials` + `CsrfTokenBadge` + `RememberMeBadge`).
- `security.yaml`/config del firewall: `guard.authenticators` → `custom_authenticators`
  (lista; el SSO se añade aquí junto al login normal), `entry_point` explícito,
  `enable_authenticator_manager: true`.
- El SSO marca el origen del usuario con `User::setOrigin(...)` (contrato existente).
- **Importante:** activar el authenticator manager desactiva Guard por completo; coordinar
  el release del bundle con el del core.

---

## 4bis. Anotaciones de sensio/framework-extra-bundle → atributos nativos

Buscar:
```bash
grep -rln "Sensio\\\\Bundle\\\\FrameworkExtraBundle" src/
```
Conversiones (todas son atributos PHP 8 nativos disponibles en SF6.4; `@Route` se mantiene
como anotación si no migras también `doctrine/annotations`):

- **@Template** → `#[Template(...)]`
  ```php
  use Symfony\Bridge\Twig\Attribute\Template;            // añadir (quitar el use de sensio)
  /** @Route(...) */
  #[Template('@Bundle/x.html.twig')]                     // encima del método; la acción sigue devolviendo el array
  public function fooAction() { ... }
  ```
- **@Security("is_granted('ROLE_X')")** → `#[IsGranted('ROLE_X')]`
  ```php
  use Symfony\Component\Security\Http\Attribute\IsGranted;
  #[IsGranted('ROLE_X')]   // a nivel de clase o de método, según donde estuviera @Security
  ```
- **@ParamConverter** (documentos ODM) → `#[MapDocument(...)]`
  ```php
  use Doctrine\Bundle\MongoDBBundle\Attribute\MapDocument;   // lo trae doctrine/mongodb-odm-bundle
  // options={"id" = "mmId"}        → #[MapDocument(id: 'mmId')] MultimediaObject $multimediaObject
  // options={"mapping": {"k":"f"}} → #[MapDocument(mapping: ['k' => 'f'])] Type $param
  public function fooAction(#[MapDocument(id: 'mmId')] MultimediaObject $multimediaObject) { ... }
  ```
  Requiere `doctrine_mongodb.controller_resolver.enabled` (true por defecto). El atributo va
  sobre el **parámetro** cuyo nombre coincide con el primer arg de `@ParamConverter`.
- **Nota (auto_mapping):** con `controller_resolver.auto_mapping: true` (por defecto), un argumento
  tipado como documento se resuelve **sin `#[MapDocument]`** usando los placeholders de la ruta como
  criterio — funciona tanto con `{id}` como con otros campos (p.ej. `{secret}` → `findOneBy(secret)`).
  Por eso, al quitar el `auto_convert` de sensio, muchos controllers que recibían documentos sin
  `@ParamConverter` **siguen funcionando** sin tocar nada. Solo añade `#[MapDocument]` si el
  auto_mapping falla (campo ambiguo o nombre que no casa).
- Verificación de equivalencia: nº de `#[Template]` == nº de `@Template`, idem `#[IsGranted]`/`@Security`
  y `#[MapDocument]`/`@ParamConverter` (cuidado: varios `#[MapDocument]` en una misma línea de
  firma → cuenta ocurrencias con `grep -o`, no líneas).
- Si el bundle tenía un bloque `sensio_framework_extra:` en su config y ya no usa sensio, quítalo.

## 5. Password: encoder → hasher

Buscar:
```bash
grep -rn "UserPasswordEncoderInterface\|Core\\\\Encoder\|encodePassword" src/
```
- `Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface`
  → `Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface`
- `->encodePassword($user, $plain)` → `->hashPassword($user, $plain)`
- Mantener el algoritmo `sha512` en `password_hashers` para no invalidar contraseñas.

En **el documento User** (si el bundle define el suyo):
```php
class User implements UserInterface, LegacyPasswordAuthenticatedUserInterface  // añadir Legacy...
public function getUserIdentifier(): string { return (string) $this->username; }  // añadir
// getSalt() se mantiene (lo exige LegacyPasswordAuthenticatedUserInterface por el hash salteado)
```

---

## 6. Email: Swiftmailer → symfony/mailer

Buscar:
```bash
grep -rn "Swift_\|Swiftmailer\|swiftmailer" src/ config/
```
Before → After:
```php
\Swift_Mailer $mailer            → Symfony\Component\Mailer\MailerInterface $mailer
new \Swift_Message()             → new Symfony\Component\Mime\Email()
->setSubject($s)                 → ->subject($s)
->setFrom($e,$n) / setSender     → ->from(new Address($e,$n)) / ->sender(...)
->addReplyTo($e,$n)              → ->replyTo(new Address($e,$n))   // replaza; addReplyTo acumula
->setTo($e)                      → ->to($e)
->setBody($b,'text/html')->addPart($b,'text/plain')  → ->html($b)->text($b)
->addBcc($e)                     → ->addBcc($e)
$mailer->send($m)  // devolvía int  → devuelve void; envolver en try/catch TransportExceptionInterface
                                       si se comprobaba el resultado.
```
Config: `swiftmailer.yaml` → `mailer.yaml` (`framework.mailer.dsn: '%env(MAILER_DSN)%'`),
`.env` `MAILER_URL` → `MAILER_DSN`, quitar el bundle de `config/bundles.php`.

---

## 7. Templating PHP → Twig

`symfony/templating` no existe en SF6. Buscar uso real (no la variable `$templating`):
```bash
grep -rn "Symfony\\\\Component\\\\Templating\|EngineInterface\|templating.engine\|@templating" src/ config/
```
Sustituir por `Twig\Environment` (`$twig->render(...)`) o `$this->render(...)` en controladores.

---

## 8. Kernel (solo si el bundle/app trae su propio Kernel)

```php
use Symfony\Component\Routing\RouteCollectionBuilder;            // →
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

protected function configureRoutes(RouteCollectionBuilder $routes): void   // →
protected function configureRoutes(RoutingConfigurator $routes): void
$routes->import($path, '/', 'glob');   // →   $routes->import($path, 'glob');  (sin prefijo)
```

---

## 9. Trusted proxies (solo apps, en `public/index.php`)

`Request::HEADER_X_FORWARDED_ALL` se eliminó:
```php
Request::HEADER_X_FORWARDED_ALL ^ Request::HEADER_X_FORWARDED_HOST
// →
Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO
```

---

## 10. Config de framework

Buscar:
```bash
grep -rn "storage_id\|session:" config/
```
- `framework.session.storage_id` → `storage_factory_id`
  (p.ej. `session.storage.mock_file` → `session.storage.factory.mock_file`).
- Servicios DI que pasen `'@session'` → `'@request_stack'` (y adaptar el constructor a RequestStack).

---

## 10bis. Servicios privados accedidos vía `$container->get(...)`

En SF6 muchos servicios del framework son **privados** y `$container->get('id')` falla
(*"... service or alias has been removed or inlined ... make it public, or use DI"*).
Típico en builders ContainerAware de KnpMenu, helpers, etc. Servicios habituales privados:
`security.authorization_checker`, `security.token_storage`. (`request_stack` es público.)

Buscar:
```bash
grep -rn "container->get('security\|container->get(\"security\|->get('security.authorization_checker'" src/
```
Opciones de arreglo:
- **Preferida:** inyectar la dependencia por constructor (`AuthorizationCheckerInterface`, etc.).
- **Mínima (consistente con el patrón de aliases del proyecto):** declarar un alias público y
  usarlo. Ej. en un `services.yaml` con `_defaults: { public: true }`:
  ```yaml
  pumukitnewadmin.authorization_checker: '@security.authorization_checker'
  ```
  y en el código `$this->container->get('pumukitnewadmin.authorization_checker')`.
- Nota: si un servicio se obtiene de `$container->get()` y antes funcionaba, comprueba si es
  público (`bin/console debug:container <id> | grep Public`); si lo es, no hay que tocarlo.

## 10ter. Acceso dinámico a propiedades sobre array/null (PHP 8 + error handler estricto)

Con SF6 (error handler) los *warnings* de PHP 8 se convierten en `ErrorException` (500), que
antes pasaban silenciosos devolviendo `null`. El típico: `$x->prop` cuando `$x` es un **array**
o `null` → *"Attempt to read property \"prop\" on array/null"*.

Buscar candidatos (acceso encadenado sin `isset`):
```bash
grep -rn "json_decode" src/   # metadatos decodificados que luego se acceden con ->
```
Arreglo: proteger con `isset(...)` (o comprobar el tipo) antes de leer la propiedad.
```php
return (int) ceil((float) $metadata->format->duration) ?? 0;            // ✗ revienta si $metadata es array/null
return isset($metadata->format->duration)
    ? (int) ceil((float) $metadata->format->duration) : 0;             // ✓
```
(Ejemplo real: `SchemaBundle/Document/MediaType/Metadata/VideoAudio::duration()` cuando la
metadata de ffprobe se había guardado como JSON array `[{...}]` en vez de objeto.)

## 10quater. mobiledetect/mobiledetectlib v2 → v4 (cambio de API, silencioso)

Si el bundle usa `Detection\MobileDetect`, en v4 **ya no se pasa el user-agent a los métodos**;
hay que fijarlo antes con `setUserAgent()`. Pasarlo a `isMobile($ua)` no da error pero
devuelve siempre `false` (bug silencioso).
```php
// v2:
$mobile = $detector->isMobile($ua) || $detector->isTablet($ua);
// v4:
$detector->setUserAgent($ua ?? '');
$mobile = $detector->isMobile() || $detector->isTablet();
```
Buscar: `grep -rn "isMobile(\$\|isTablet(\$\|->version(" src/`

## 10quinquies. Opciones de comando que chocan con las globales de SF6

SF6.1+ añade opciones **globales** a la consola del FrameworkBundle, en especial **`--profile`**
(perfilado de comandos). Si un comando propio define `addOption('profile', ...)`, al ejecutarlo
falla con *"An option named \"profile\" already exists"* (`InputDefinition` line ~234).
Buscar:
```bash
grep -rn "addOption('profile'\|addOption(\"profile\"" src/
```
Arreglo: **renombrar la opción** del comando (p.ej. `profile` → `encoding-profile`) y actualizar
`getOption()`, el texto de ayuda y **todos los que invocan el comando** (listeners/procesos que
construyen `--profile=...`). Verificar con `bin/console <cmd> --help` (aparecen las dos: la propia
renombrada y la global `--profile`).
(Caso real: `pumukit:import:inbox` / `pumukit:import:multimedia:file` + sus listeners de inbox.)

## 11. Tests (PHPUnit / SF6)

- [ ] `phpunit.xml.dist`: añadir `<server name="KERNEL_CLASS" value="App\Kernel"/>`
      (SF6 ya no autodetecta el Kernel).
- [ ] Servicios **privados** (`security.token_storage`, `security.authorization_checker`, …)
      obtenidos del contenedor: usar `static::getContainer()` en vez de
      `static::$kernel->getContainer()`.
      ```bash
      grep -rn "kernel->getContainer()->get('security" src/
      ```
- [ ] El servicio `session` ya no existe en el contenedor: usar `request_stack`.
- [ ] Mocks:
  - `UserPasswordEncoder` → mockear la clase concreta
    `Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher` (la interfaz declara los
    métodos como `@method`, un mock de la interfaz no los tiene).
  - `\Swift_Mailer` → `MailerInterface`.
  - `RequestStack::getMasterRequest` → `getMainRequest`.

---

## 11bis. Caché obsoleta tras actualizar paquetes (Twig compilado / profiler)

Tras subir un bundle, en `var/cache/dev` pueden quedar **plantillas Twig compiladas antiguas**
y datos del profiler de la versión anterior que `bin/console cache:clear` NO purga (frecuente
con Docker, por la propiedad de los ficheros creados en el contenedor). Síntoma típico:
error en una plantilla de vendor referenciando un método/propiedad ya eliminado, p.ej.
*"Neither the property authenticatorManagerEnabled nor methods ... exist ... in
SecurityDataCollector in @Security/Collector/security.html.twig"*.

Arreglo: limpieza dura.
```bash
docker compose exec -w /srv/pumukit php sh -c 'rm -rf var/cache/dev && php bin/console cache:clear'
```
**Cuidado con permisos (Docker):** tras un `rm -rf var/cache` + `cache:clear` por CLI, los
directorios recreados quedan con el dueño del usuario CLI y php-fpm (web) puede no poder
escribir cachés on-demand (ODM hydrators/proxies) → 500 *"Your hydrator directory must be
writable"*. Si pasa, dar permisos: `chmod -R 0777 var/cache var/log` (entorno dev).

## 11ter. Absorber un bundle externo `Pumukit\*` al core

Cuando un bundle externo es obligatorio y su namespace ya es `Pumukit\...` (mapea a `src/Pumukit/`):
1. Copiar `vendor/teltek/<bundle>/{Controller,DependencyInjection,Resources,*.php}` →
   `src/Pumukit/<Bundle>/` (sin `composer.json`/README/CHANGELOG/dotfiles).
2. `composer remove --no-scripts <paquete>` → elimina la copia de vendor. PSR-4 `Pumukit\` (src)
   pasa a servir la clase. **Gotcha:** la receta de Flex **quita el bundle de `config/bundles.php`**;
   hay que **re-añadirlo** (la clase es la misma, ahora carga desde src).
3. **Gotcha:** buscar configs de la app que apunten a la ruta vendor del bundle y corregirlas:
   ```bash
   grep -rn "vendor/teltek\|vendor/<vendor>" config/
   ```
   (p.ej. `config/packages/pumukit_stats_ui.yaml` tenía
   `resource: '../../vendor/teltek/.../Controller/'` → cambiar a `'../../src/Pumukit/<Bundle>/Controller/'`).
4. Migrar las anotaciones sensio del bundle (secciones 4bis) y revisar SF6.
5. `php bin/console assets:install public --symlink --relative` (republica `Resources/public`;
   el símlink antiguo apuntaba a vendor).
6. `rm -rf var/cache/dev` + `cache:clear`; verificar rutas (`debug:router`) y página real.
   Las rutas via `@<Bundle>/Resources/config/routing.yml` siguen funcionando (notación de bundle).

## 11quater. Comandos de consola (SF6.x)

Patrones SF6 que aparecen en bundles con comandos propios. Buscar candidatos:
```bash
grep -rn "extends Command\|static \$defaultName\|return -1\|return 0;\|hasOption(\|hasArgument(" src/
```

- **`static $defaultName` deprecado** (SF 6.1, eliminado en SF 7). Sustituir por atributo:
  ```php
  use Symfony\Component\Console\Attribute\AsCommand;

  #[AsCommand(name: 'bundle:foo:bar', description: '…')]
  class FooCommand extends Command { ... }
  ```
  Tras esto, `setName()`/`setDescription()` en `configure()` ya no hacen falta.

- **Exit codes negativos**: `return -1` (o `return 0`) → `Command::SUCCESS`/`FAILURE`/`INVALID`.
  Casos típicos: filtros tempranos que abortan sin error real (eventos inotify no relevantes,
  condiciones de entrada que no son fallos) → `Command::SUCCESS`, no `-1`. SF6 acepta `-1`
  pero lo registra como fallo del comando, y los scripts/listeners que reaccionan al exit
  code interpretan eso mal.

- **`hasOption()` / `hasArgument()` ≠ "el usuario lo pasó"**. Devuelven `true` si la
  opción/arg está **declarada**, no si llegó valor. El check correcto:
  ```php
  $profile = $input->getOption('encoding-profile') ?? $defaultProfile;
  $arg = $input->getArgument('description');
  $payload = null !== $arg ? [...] : [];
  ```
  Falsos `$default ?? null` por mal uso de `hasOption` pasan silenciosos y desactivan los
  defaults que el comando documenta.

- **Registro**: con `autoconfigure: true` (default en `services.yaml`), un comando que
  extiende `Command` ya recibe el tag `console.command` automáticamente. El tag explícito
  en YAML (`tags: ['console.command']`) es redundante; se puede quitar o dejar — solo cosmético.

- **Clash con `--profile` global**: ver 10quinquies.

---

## 11sexies. Limpieza de perfiles de transcodificación (encoder.yaml)

Solo aplica a bundles que **definan sus propios profiles** bajo `pumukit_encoder.profiles.*`
(extensión del esquema del `EncoderBundle`) o que repliquen un esquema equivalente.

Tras la auditoría del core: muchos campos históricamente declarados en cada profile **nunca
se leen** — los valores reales se obtienen probing del fichero con `ffprobe`, el comando
ejecutado es el del `bat:` (que ya incluye el binario hardcoded), y el `streamserver.host`
viene del CPU config, no del profile.

Campos **a borrar** de cada profile y del tree de configuración del bundle, si los define:

- A nivel de profile: `format`, `codec`, `mime_type`, `bitrate`, `framerate`, `channels`,
  `app`, `rel_duration_size`, `rel_duration_trans`, `file_cfg`, `prescript`.
- Dentro de `streamserver`: `name`, `type`, `host`, `description`. El antiguo `name`
  era solo metadata para mensajes de error; ahora el error de `validateProfilesDir()`
  identifica el profile por su clave del array (mucho más útil cuando varios profiles
  comparten el mismo streamserver lógico).

Campos **load-bearing** (no tocar): `bat`, `target`, `tags`, `extension`,
`streamserver.{dir_out,url_out}`, `display`, `wizard`, `master`, `audio`, `image`,
`document`, `generate_pic`, `nocheckduration`, `resolution_hor`, `resolution_ver`,
`downloadable`.

Buscar:
```bash
grep -nE "format:|codec:|mime_type:|bitrate:|framerate:|channels:|app:|rel_duration_(size|trans):|file_cfg:|prescript:" config/packages/*encoder*.yaml
grep -nE "scalarNode\('(format|codec|mime_type|bitrate|framerate|channels|app|file_cfg|prescript|name|host|description)'\)|integerNode\('(channels|rel_duration_size|rel_duration_trans)'\)|enumNode\('type'\)" src/
```

Cleanup completo:
1. Quitar las claves del yaml (incluye las anidadas bajo `streamserver`).
2. Quitar los nodos correspondientes de `Configuration.php` del bundle.
3. Si el bundle definía constantes `STREAMSERVER_*` (store/download/wmv/fms/red5) que
   solo se usaban en el `enumNode('type')`, borrarlas también — junto con sus referencias
   en tests/fixtures.
4. Limpiar las claves de los fixtures de tests (`'type' => …`, `'host' => …`, `'app' => …`,
   etc.) y los `use` huérfanos que dejan de referenciarse.
5. Actualizar la doc del bundle (típicamente `Resources/doc/Configuration.md`).
6. `make cc` (dev y prod) para confirmar que el schema sigue cargando sin errores.

Si el bundle expone perfiles para que terceros configuren los suyos en la app final,
documenta el cambio como **breaking change** del bundle: las apps que pasen los campos
borrados verán fallar la validación del schema.

---

## 12. Verificación por bundle

1. `make composer CMD="update -W"` (resolver constraints).
2. `bin/console cache:clear` (dev y prod) sin errores.
3. Suite de tests verde.
4. Si el bundle tiene UI/flujo crítico (login SSO, players…), prueba manual real:
   los tests con BD vacía no detectan todos los 500 de runtime.

## Deprecations pendientes (no bloqueantes SF6, sí en SF7)
- `Bundle::build()` / `Bundle::getContainerExtension()` deberían declarar tipos de retorno
  (`: void` / `: ?ExtensionInterface`). Aparecen como `User Deprecated` al limpiar caché.
