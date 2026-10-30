# UPGRADE FROM 5.x to 6.0

This document lists the **code-level changes** in PuMuKIT 6.0 that affect maintainers of
PuMuKIT bundles and of projects that customize the core (own documents, services,
controllers or overridden templates). The deployment steps (Docker images, MongoDB server
upgrade, indexes) are described in [doc/upgrade/from5.1to6.0.md](doc/upgrade/from5.1to6.0.md).

> PuMuKIT 6.0 targets **PHP 8.5**, **Symfony 7.4 LTS** and **MongoDB 9.0** with the 2.x PHP
> driver. This document is completed as each step of the migration lands on the `6.0.x` branch.

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

### Dependencies

- **MonologBundle 4.** Custom Monolog configuration must not use the options and handler
  types removed in 4.0: `excluded_404s` (use `excluded_http_codes`),
  `console_formater_options` (use `console_formatter_options`), the `elasticsearch` type (use
  `elastica` or `elastic_search`), the `mongo` type (use `mongodb`) and the `sentry`/`raven`
  types (use a `service` handler with `sentry/sentry-symfony`).
- **API Platform 5.** The `/api/v1` resources are declared with `#[ApiResource]` on the
  documents of `SchemaBundle`. Bundles that declare their own resources must follow the
  API Platform 5 changes: the legacy PropertyInfo `Type` system is removed (use
  `symfony/type-info`), JSON:API uses `use_iri_as_id: false` by default, and
  `ApiTestCase` moves to the `api-platform/test` package. The OpenAPI document of
  `/api/v1/docs` is now generated as OpenAPI 3.2.

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

## MongoDB driver 2 and Doctrine ODM 2.17

The core runs on `ext-mongodb` 2.5, `mongodb/mongodb` 2.5 and Doctrine ODM 2.17. Changes that
affect bundles and custom code:

- Import the ODM mapping attributes from `Doctrine\ODM\MongoDB\Mapping\Attribute` instead of
  `Doctrine\ODM\MongoDB\Mapping\Annotations` (deprecated since ODM 2.16, removed in 3.0):
  `use Doctrine\ODM\MongoDB\Mapping\Attribute as MongoDB;`.
- Aggregation builders: call `->getAggregation()->getIterator()` (or `->getAggregation()->execute()`)
  instead of `->execute()` on the builder or on a stage.
- `DocumentManager::clear()` no longer accepts a document class. To discard stale managed
  documents after a query that updates them in the database, reload them with a query that
  uses `->refresh()`.
- The BSON functions (`MongoDB\BSON\fromPHP()`, `toPHP()`, `toJSON()`, ...) are removed; use
  `MongoDB\BSON\Document`.
- `MongoDB\BSON\UTCDateTime` only accepts an `int` (milliseconds), a `DateTimeInterface` or an
  `Int64`, not a `string` or a `float`.
- `MongoDB\BSON\Regex` requires a `string` pattern. Passing `null` is deprecated; read
  optional request values with `$request->query->getString()`.
- `MongoDB\Collection::mapReduce()` is removed (use an aggregation pipeline), and `drop()`,
  `dropIndex()`, `rename()` and the `Database::createCollection()`/`drop*()` methods return
  `void` instead of the command result.
- `MongoDB\Driver\ReadPreference` takes the mode as a string (`'primary'`, ...); the integer
  constants and `getMode()` are removed.

## Tests: PHPUnit 13

The core runs its suite on PHPUnit 13, which fails on any deprecation, PHPUnit deprecation or
PHPUnit notice. Bundle test suites must follow the same rules:

- Docblock annotations (`@dataProvider`, `@covers`, `@coversNothing`, `@group`, ...) are no
  longer read: use the attributes of `PHPUnit\Framework\Attributes` (Rector:
  `withAttributesSets(phpunit: true)`).
- A mock without expectations triggers a notice: create test doubles that only return values
  with `createStub()` instead of `createMock()`/`getMockBuilder()->getMock()`.
- `expects($this->any())` is deprecated: use a stub and `->method(...)->willReturn(...)`.
- Migrate `phpunit.xml.dist` with `vendor/bin/phpunit --migrate-configuration`.

## Frontend assets

PuMuKIT 6.0 serves the public files of the bundles through Symfony AssetMapper. Every
bundle `Resources/public` directory is mapped automatically under its usual prefix
(`bundles/pumukitwebtv/...`), so `{{ asset('bundles/...') }}` now returns a versioned URL
(`/assets/bundles/pumukitwebtv/js/template_video-<hash>.js`).

- Reference bundle assets **without** a leading slash: `asset('bundles/pumukitwebtv/...')`.
  A path starting with `/` is not mapped and is served as a plain static file.
- jQuery and Bootstrap are shared from `bundles/pumukitcore/vendor/` and the copies
  that each bundle had are removed. Overridden templates must load the shared files:

  | Removed | Replacement |
  |---|---|
  | `bundles/pumukitnewadmin/js/jquery-1.10.2.min.js` | `bundles/pumukitcore/vendor/jquery/jquery.min.js` |
  | `bundles/pumukitwebtv/js/jquery.min.js` | `bundles/pumukitcore/vendor/jquery/jquery.min.js` |
  | `bundles/pumukitnewadmin/js/bootstrap.min.js`, `bundles/pumukitwebtv/js/bootstrap.min.js` | `bundles/pumukitcore/vendor/bootstrap5/js/bootstrap.bundle.min.js` |
  | `bundles/pumukitnewadmin/css/bootstrap.min.css` | `bundles/pumukitcore/vendor/bootstrap5/css/bootstrap.min.css` |
  | `bundles/pumukitwebtv/css/bootstrap.min.css` | `bundles/pumukitcore/vendor/bootstrap5/css/bootstrap.min.css` and `bundles/pumukitwebtv/css/webtv.css` |
  | `bundles/pumukitwebtv/js/moment-with-locales.min.js`, `bundles/pumukitstatsui/vendor/moment/moment.min.js` | nothing (moment is removed, see below) |
  | `bundles/pumukitnewadmin/js/jquery-ui-1.11.3.custom.min.js`, `bundles/pumukitnewadmin/css/jquery-ui-1.11.3.custom*.min.css` (3 files) | nothing (jQuery UI is removed, see below) |
  | `bundles/pumukitwebtv/js/bootstrap-datetimepicker.min.js`, `bundles/pumukitwebtv/css/bootstrap-datetimepicker(.min).css` | nothing (bootstrap-datetimepicker is removed, see below) |

  The shared versions are jQuery 4.0.0 and Bootstrap 5.3.8.
- **Dates without libraries.** moment, bootstrap-datetimepicker and daterangepicker are removed
  (`bundles/pumukitcore/vendor/moment/`, `bundles/pumukitcore/vendor/bootstrap-datetimepicker/`,
  `bundles/pumukitstatsui/vendor/daterangepicker/`). The date filters of the portal search are
  native `<input type="date">` fields (the `webtv--search` controller still turns the text fields of
  an overridden filter into date fields), and the statistics use a predefined range select and two
  native date fields (`from`, `to` and `preset` targets of `stats-ui--stats` instead of `dateRange`).
- **jQuery 4.** The backoffice and portal layouts load jQuery 4.0.0 without jQuery Migrate, and
  `bundles/pumukitcore/vendor/jquery-migrate/jquery-migrate.min.js` is removed: the scripts of a
  project must not use the APIs removed in jQuery 3 and 4. A project can load its own copy of
  jQuery Migrate 4 while its scripts are migrated, to restore them and log a `JQMIGRATE` warning
  for each one. `bin/console pumukit:check:assets` reports the jQuery 4 utilities used in the
  templates. Removed in jQuery 4 ([upgrade guide](https://jquery.com/upgrade-guide/4.0/)):
  - `$.trim`, `$.isArray`, `$.isFunction`, `$.isNumeric`, `$.isWindow`, `$.nodeName`, `$.now`,
    `$.parseJSON`, `$.type`, `$.unique`, `$.camelCase` and `$.cssProps`: use `String.prototype.trim()`,
    `Array.isArray()`, `typeof`, `Number.isFinite()`, `Date.now()`, `JSON.parse()`, `$.uniqueSort()`…
  - `.toggleClass(boolean)` without a class name.
  - `$.ajax` with `dataType: 'json'` and a callback no longer becomes JSONP (use `dataType: 'jsonp'`),
    and a script response is only run with `dataType: 'script'` or `$.getScript()`.
  - `.css()` only appends `px` to the dimension, margin, padding, border width and position
    properties; write the unit for the others.
  - `focusin`/`focusout` follow the native order relative to `focus`/`blur`.

  The event shorthands (`.click(fn)`, `.change(fn)`…), `.bind()`, `.delegate()`, `$.proxy()` and
  `$.Deferred` are still available. The APIs removed in jQuery 3 found in PuMuKIT code were:
  - `$.get(...).success()`/`.error()`/`.complete()`: use `.done()`/`.fail()`/`.always()`.
  - `.size()`: use `.length`.
  - `.load(function)`, `.unload()` and `.error(function)` as event shorthands: use
    `.on('load', ...)`, `.on('unload', ...)`, `.on('error', ...)`.
- **jQuery UI is removed.** The backoffice layout no longer loads jQuery UI, and
  `bundles/pumukitcore/vendor/jquery-ui/`, `@PumukitNewAdmin/jquery-ui-autocomplete.css.twig` and its
  route `pumukit_jquery_ui_autocomplete_css` (`/admin/jquery_ui_autocomplete.css`) are removed. The
  autocompletes of the backoffice (people of a multimedia object, channel and series of a live
  event) use the `SuggestInput` module of the bundle, importable as `@pumukit/new-admin/suggest`:

  ```js
  import { SuggestInput, remoteSource } from '@pumukit/new-admin/suggest';

  new SuggestInput(input, {
      source: remoteSource(url, (item) => ({ id: item.id, text: item.label, description: item.desc, value: item.value })),
      minLength: 2,
      select: (item) => { hiddenInput.value = item.id; },
  });
  ```

  `remoteSource()` requests `url?term=…` and maps each item of the JSON list to `text` (the
  option), `description` (an optional second line) and `value` (written in the field when it is
  chosen). The list is a Bootstrap `dropdown-menu` with the `pmk-suggest-menu` class, and the field
  has the `pmk-suggest-loading` class while it waits for the answer. Overridden templates that call
  `.autocomplete()` must use it; `pumukit:check:assets` reports those calls.
- **Inline styles.** The core templates no longer use `style` attributes: each declaration is a
  `pmk-<property>-<value>` class of `bundles/pumukitcore/css/pumukit-utilities.css` (for example
  `style="margin-top: 10px"` → `class="pmk-margin-top-10"`), so that the pages can run under a Content
  Security Policy without `unsafe-inline` for styles. The portal layout loads it after the `stylesheets`
  block; an overridden `PumukitWebTVBundle/Layout/template.html.twig` must load it too. The classes
  keep the precedence of the inline styles over the other stylesheets (`.pmk-…:not(#pmk)`), and the
  styles that scripts set on `element.style` still win over them. `display: none` is the
  `pmk-display-none` class, so `$(element).show()` keeps working.
  The `<style>` blocks are gone too: the custom CSS of a series style is served by the
  `pumukit_webtv_series_style` route (`/series/style/{id}.css`) and linked from the `stylesheets`
  block of `@PumukitWebTV/Series/template.html.twig`; the live iframes load
  `bundles/pumukitwebtv/css/live/iframe.css` and use the `live-iframe` body classes; the rules of the
  playlist modal, the UNESCO drop zone, the user statistics and the naked backoffice menu are in
  `@PumukitNewAdmin/pumukit.css.twig`. The values that are only known at runtime are set by the
  controllers: the storage bars of the dashboard (`admin--storage-bar`), the aspect ratio of the
  thumbnail generator and the text colour of the live poster (`text-color` value of
  `live-player--sessions`). The Encoder colours of a job status come from the
  `job_status_class(status, property)` macro, and the form types use `pmk-resize-vertical` instead
  of `style: resize:vertical`.
- Run `bin/console pumukit:check:assets` in the project before upgrading: it lists every
  `bundles/...` asset and every `@Pumukit.../....twig` template referenced by the templates in
  `templates/` (or `--path`) that no installed bundle provides, with its replacement when it was
  moved, and an overridden portal layout without `importmap('pumukit')`. A missing JavaScript file
  does not break the page, it only shows a 404 in the browser console.
- Bundles that ship files whose JavaScript loads other files by relative URL (like the Paella
  build) must exclude them from AssetMapper with `framework.asset_mapper.excluded_patterns`.
- The Paella player is built with a content hash in its file name
  (`teltek-paella-player.<hash>.js`) and a `manifest.json` that the `paella` asset package reads.
  An overridden `@PumukitPaellaPlayer/PaellaPlayer/index.html.twig` must load
  `{{ asset('teltek-paella-player.js', 'paella') }}` instead of
  `bundles/pumukitpaellaplayer/paella/dist/teltek-paella-player-<date>.js`.
- Production builds must run `bin/console asset-map:compile` after `assets:install` (the
  Docker image does it).

### Bootstrap 5

The backoffice, the portal, the inbox and the live pages use Bootstrap 5.3.8
(`bundles/pumukitcore/vendor/bootstrap5/css/bootstrap.min.css` and `bundles/pumukitcore/vendor/bootstrap5/js/bootstrap.bundle.min.js`, which includes Popper). Bootstrap 3, Bootstrap Material Design,
its ripples and SnackbarJS are removed (`bundles/pumukitcore/vendor/bootstrap/`,
`bundles/pumukitwebtv/css/bootstrap.min.css` and its glyphicons, `bundles/pumukitnewadmin/js/material.min.js`,
`ripples.min.js`, `snackbar.min.js`, `css/material-wfont.min.css`, `ripples.min.css`, `snackbar.min.css`).
`bin/console pumukit:check:assets` reports overridden templates that still load them.

- Two stylesheets keep the previous look on top of Bootstrap 5: `bundles/pumukitnewadmin/css/backoffice.css`
  (backoffice, replaces Material) and `bundles/pumukitwebtv/css/webtv.css` (portal: 14 px base,
  Bootstrap 3 container widths and gutters, buttons, panels, `.thumbnail`). An overridden layout must
  load them after the Bootstrap stylesheet. The `mdi-*` icons are kept in `bundles/pumukitnewadmin/css/mdi-icons.css`.
- The pages use the system font stack of Bootstrap 5 instead of Roboto (backoffice) and Helvetica (portal).
- Overridden templates must be migrated to the Bootstrap 5 markup. The usual changes are:
  - `data-toggle`, `data-target`, `data-dismiss`, `data-parent`… → `data-bs-*`.
  - Columns need a `.row` parent (Bootstrap 5 rows are flex, columns no longer float); `col-xs-N` → `col-N`,
    `col-*-offset-N` → `offset-*-N`; clearfix `div`s between columns → `<div class="w-100 d-none d-md-block"></div>`.
  - `panel*` → `card*`, `well` → `card card-body` (or `pmk-well` in the backoffice), `label label-*` → `badge text-bg-*`,
    `btn-default` → `btn-secondary`, `btn-xs` → `btn-sm`, `img-responsive` → `img-fluid`, `pull-left/right` → `float-start/end`,
    `text-left/right` → `text-start/end`, `hidden-xs` → `d-none d-sm-block`, `sr-only` → `visually-hidden`,
    `input-group-addon` → `input-group-text`, `input-group-btn` wrappers removed, `form-group` → `mb-3`,
    `control-label` → `form-label`, `help-block` → `form-text`, selects use `form-select`.
  - `embed-responsive embed-responsive-16by9` → `ratio ratio-16x9` (`embed-responsive-item` has no style any more and
    is kept as a CSS hook in the core templates).
  - Navbars: `navbar-expand-md`, `navbar-toggler` with `navbar-toggler-icon`, `nav-item`/`nav-link` on the menu items,
    `active` on the link instead of the `li`; dropdown items need `dropdown-item`; `caret` spans are removed.
  - Tabs: `nav-link` on the tab links, `active` on the link, `tab-pane fade show active` on the visible pane.
  - Modals: the close button is `<button class="btn-close" data-bs-dismiss="modal">` after the title. Bootstrap 5 no
    longer loads a remote URL into a modal: the backoffice entrypoint loads the `href` of a trigger with
    `data-bs-toggle="modal"` into the target `.modal-content` and triggers `loaded.bs.modal`. With the jQuery
    bridge, call `.modal('show')`.
  - Carousel: `item` → `carousel-item`, indicators are `button`s with `data-bs-slide-to` starting at 0, the controls
    are `carousel-control-prev/next`.
  - Glyphicons → Font Awesome 7 (`fa-solid fa-*`, see below).
- `[hidden]` is `display: none !important` in Bootstrap 5: `$(element).show()` no longer reveals an element with the
  `hidden` attribute; set `element.hidden = false`.
- The backoffice messages `window.success()` and `window.no_success()` show Bootstrap 5 toasts instead of SnackbarJS;
  `$.snackbar()` and `data-toggle="snackbar"` are removed.
- The `col_calculator()` Twig function returns Bootstrap 5 classes (`col-12 col-sm-6 col-md-3`…).

### Font Awesome 7

The backoffice (Font Awesome 5.11.2, `bundles/pumukitnewadmin/lib/fontawesome/`) and the portal
(Font Awesome 4.7, `bundles/pumukitwebtv/css/font-awesome.min.css` and `bundles/pumukitwebtv/fonts/`)
share Font Awesome Free 7.3.1 from `bundles/pumukitcore/vendor/fontawesome/css/all.min.css`; the old
copies are removed and `bin/console pumukit:check:assets` reports the templates that still load them.

- The core templates use the current class names: `fa-solid`, `fa-regular` or `fa-brands` plus the
  Font Awesome 7 icon name (`fa fa-search` → `fa-solid fa-magnifying-glass`, `fa fa-times` →
  `fa-solid fa-xmark`, `fa fa-sign-in` → `fa-solid fa-right-to-bracket`, `fa fa-twitter` →
  `fa-brands fa-twitter`). The short prefixes (`fa`, `fas`) and the Font Awesome 5 and 6 names still
  work as aliases, but the names that only existed in Font Awesome 4 (`fa-files-o`,
  `fa-calendar-check-o`, `fa-circle-o-notch`, `fa-linkedin-square`, `fa-youtube-play`…) show nothing:
  `pumukit:check:assets` lists them. The equivalences are in the `shims.yml` file of the
  `@fortawesome/fontawesome-free` package.
- Font Awesome 7 gives the icons a fixed width; `backoffice.css` and `webtv.css` keep the previous
  automatic width with `--fa-width: auto`.
- The icons are decorative for screen readers: give a meaning to an icon without text with
  `aria-label` or a `visually-hidden` text.
- Only the `woff2` fonts are shipped.
- The Material Design icons of the backoffice (`mdi-*`, from Bootstrap Material Design) are
  Font Awesome 7 icons now, and the Material Design Icons font is removed: `mdi-editor-mode-edit` →
  `fa-solid fa-pencil`, `mdi-content-clear` → `fa-solid fa-xmark`, `mdi-action-info` →
  `fa-solid fa-circle-info`, `mdi-social-group` → `fa-solid fa-users`… `pumukit:check:assets` reports
  the `mdi-*` classes left in the templates of a project with the equivalent that the core uses
  (`CheckAssetsCommand::MATERIAL_ICONS`). The publication status icons of
  `PumukitAdminExtension` are `fa-solid fa-wifi` (published or hidden, told apart by their opacity),
  `fa-solid fa-lock` (blocked) and `fa-solid fa-ban` (no channels); the tag tree icons take their glyph
  from the `--fa` variable of `i.tag_icon`.

### JavaScript in templates (Stimulus)

The inline JavaScript of the templates (`<script>` blocks, `on*=` attributes and the
`*.js.twig` files) is being moved to [Stimulus](https://stimulus.hotwired.dev/) controllers
(`symfony/stimulus-bundle`) loaded through the importmap, so that the pages can run under a
Content Security Policy without `unsafe-inline`. The CoreBundle declares the importmap
(`Resources/importmap/importmap.php`), the downloaded packages and the `pumukit` entrypoint.

- The core layouts render `{{ importmap('pumukit') }}` at the end of the `javascripts` block
  (`@PumukitWebTV/Layout/template.html.twig` and `@PumukitNewAdmin/layout.html.twig`).
  **An overridden portal layout must add it**: without it the controllers do not start and the
  migrated features silently do nothing, also in the back-office, whose layout extends the
  portal one.
- Data reaches the JavaScript through `data-*` attributes: values
  (`data-<controller>-<name>-value`), targets and actions. Controllers are named
  `<bundle>--<name>`, e.g. `data-controller="encoder--job"`. URLs are generated in the template
  and passed as values.
- Translations: `import { trans } from '@pumukit/translator';` (`symfony/ux-translator`, `messages`
  domain, locale taken from `<html lang>`).
- A bundle registers its controllers by calling
  `FrontendAssets::register($container, dirname(__DIR__), '@pumukit/<alias>')` from the `prepend()`
  of its extension, and puts them in `Resources/assets/controllers/<alias>/<name>_controller.js`
  with the `/* stimulusFetch: 'lazy' */` directive, so they are only downloaded by the pages that use
  them.
- A project can add its own controllers with `stimulus.controller_paths` in
  `config/packages/stimulus.yaml`. Their names must not collide with the core ones.
- The Flex recipes of `symfony/stimulus-bundle` and `symfony/ux-translator` may add `assets/` and
  `config/packages/ux_translator.yaml` to the project. PuMuKIT does not use them; they can be removed.

Removed templates and globals:

| Removed | Replacement |
|---|---|
| `@PumukitCore/Upload/uppy_tus.js.twig`, `@PumukitCore/Upload/uppy_dispatch.js.twig` | `{% include '@PumukitCore/Upload/uppy_tus.html.twig' %}` outside the `<script>` tag |
| `uppyTUS` global variable | the `core--uploader` controller (see "File uploads (Uppy 6)") |
| `window.encoder_reload_timeout` | the `encoder--stats` controller |
| `bundles/pumukitwebtv/js/announces_ajax.js` and the `url_latestuploads_pager` global | the `webtv--announces` controller (see `@PumukitWebTV/Announces/template.html.twig`) |
| `@PumukitBaseLivePlayer/Advance/Sessions/*.js.twig` | `{% include '@PumukitBaseLivePlayer/Advance/sessions.html.twig' %}` outside the `<script>` tag |
| `resizeFrame()` (player iframe) | `data-controller="webtv--player-frame"` on the iframe; cinema mode is its `cinema` value |
| `submitForm()`, `toggleDateSelect()` (search filters) | the `webtv--search` controller on `#form_search` |
| `postChatMessage()`, `updateChat()` (live chat) | the `webtv--chat` controller |
| `insertCookieUser()`, `setCookie()`, `getCookie()` (cookie banner) | the `webtv--cookies` controller |
| `bundles/pumukitwebtv/js/template_video.js` and the `dynamic_image` class | `data-controller="webtv--dynamic-pic"` on the image, with the `dynamic` and `static` values (see `@PumukitWebTV/Resources/template_video.html.twig`) |
| `bundles/pumukitwebtv/js/jquery.countdown.js` | the `live-player--sessions` controller keeps the countdown itself |
| `bundles/pumukitwebtv/js/modernizr-custom.js` and the `Modernizr` global | native feature detection (`'ontouchstart' in window`) |
| `@PumukitStatsUI/Stats/js.html.twig` and its `window.*` functions (`getAjaxRequest`, `prepareSearch`, `createPager`…) | the `stats-ui--stats` controller on the container of `@PumukitStatsUI/Stats/index.html.twig`; the API URLs are its `urls` value |
| `bundles/pumukitstatsui/vendor/d3/`, `bundles/pumukitstatsui/vendor/nvd3/` | Chart.js 4.5.1 from the importmap, loaded by the `stats-ui--stats` controller |

`pumukit:check:assets` reports the use of these globals and of the `dynamic_image` class in the
project templates unless the
project defines them itself. The portal templates without inline JavaScript left are the ones
of Paella, which keeps its own build.

Generic controllers available to any template: `core--toggle` (shows or hides the elements of a
selector), `core--select` (selects the text of an input on click, use `click->core--select#select`)
and `core--external-script` (loads a third-party script once, by id).

Stimulus listens to the `input` event by default on `<input>` elements: declare the event
(`click->`, `change->`, `blur->`) when another one is needed.

## Backoffice JavaScript (NewAdminBundle)

The backoffice moves its inline JavaScript (`onclick`, `onsubmit`, `<script>` blocks) and the
global functions of `@PumukitNewAdmin/pumukit.js.twig` to Stimulus controllers. Only the editor of
the Paella video editor bundle keeps inline JavaScript.

`@PumukitNewAdmin/pumukit.js.twig` and its `pumukitnewadmin_js` and `pumukitnewadmin_locale_js`
routes are removed. The admin layout loads the `@pumukit/new-admin/backoffice` importmap
entrypoint next to `pumukit` (`{{ importmap(['pumukit', '@pumukit/new-admin/backoffice']) }}`): a
project or bundle that overrides `@PumukitNewAdmin/layout.html.twig` must replace its
`<script src="{{ path('pumukitnewadmin_locale_js') }}">` with that call. The module keeps the global
`success()` and `no_success()` functions, the Material and tooltip initialisation, the reload on a
403 AJAX response, the cleanup and focus of the `.pmk-modal` modals and the position of the modals
of the naked backoffice (now read from the `data-naked-backoffice` attribute of the layout). Opening
`#myModal` empties only its own content, no longer the content of every modal of the page. The
last open tab of the multimedia object, series and playlist editors is remembered by the
`admin--tabs` controller (`key` value) instead of `init_tabs()`. Removed as unused: the global
`$.ajaxSetup({cache: true})` (the jQuery 3 default already caches every request but `$.getScript()`)
and the NGINX error check of the `#iframeUpload` upload iframe, which no template has any more.

The admin layout wraps the page in `admin--actions`, so these actions work in any backoffice
template, including the content of `#myModal` loaded by AJAX:

| Action | Params | Replaces |
|---|---|---|
| `click->admin--actions#remove` | `url`, `target`, `confirm`, `success`, `error`, `blocked` (alert instead of deleting), `refresh` (JSON list of `{target, url}`; a target with a `data-refresh-url` attribute is reloaded from that URL instead), `reload` + `errorMarker` (DELETE then reload `target`), `hideModal` | `submit_delete_*()` |
| `click->admin--actions#preview` | `name`, `url` | `click_fila_url()` |
| `click->admin--actions#checkAll` | `name` | `click_checkbox_all()` |
| `click->admin--actions#load` | `url`, `target`, `data` (JSON), `confirm`, `success` (not shown when the request fails, `no_success()` is called instead) | `$('#…').load(…)` in `onclick` |
| `click->admin--actions#navigate` | `url` | `window.location.href = …` in `onclick` |
| `click->admin--actions#cancel` | | `onclick="return false;"` |
| `click->admin--actions#edit` | `name`, `show`, `update` | `click_fila_edit_urls()` |
| `click->admin--actions#create` | `url`, `method`, `key`, `name`, `list`, `listUrl`, `showUrl`, `editUrl` (`__id__`), `reset`, `success` | the "New" buttons of series and playlists |
| `click->admin--actions#sort` | `url` (`__type__`), `target`, `type`, `selected`, `selectedKey` | the sort links of the list headers |
| `change->admin--actions#go` | `url` (the value is appended) | the pager inputs |
| `click->admin--actions#toggle` | `selectors` (JSON list) | `$('#…').toggle()` in `onclick` |
| `change->admin--actions#send` | `url` (`__value__`), `reload` | the head and tail selects |
| `change->admin--actions#validateRange` | `from`, `to`, `message` | date range checks |
| `invalid->admin--actions#validity` | `message` | `oninvalid="setCustomValidity(…)"` in form types |
| `click->admin--actions#magicUrl` | `url`, `template` (`__secret__`), `confirm`, `success`, `input`, `link` | `submit_generate_magic_series()` |
| `click->admin--actions#notifyParent` | `message` (JSON) | `parent.window.postMessage(…, '*')` |
| `click->admin--actions#openModal` | `url` | `dblclick_preview_mm()` in the multimedia object list |
| `click->admin--actions#request` | `url`, `method` (POST), `confirm`, `success`, `error`, `reload` | the archive button |
| `click->admin--actions#copy` | `input` (id) | the copy buttons of the i18n fields |
| `click->admin--actions#translate` | `url`, `locale`, `field` (`input` or `textarea`), `question`, `error`; a double click skips the prompt | `pmk_translate()` |

`remove` also takes `notFound` (go to that URL when the response is a 404) and `clear`
(`{name, id, message}`): when the deleted row is the selected one,
`#preview_<name>` shows `message` and `#edit_<name>` is emptied. Action params are parsed as JSON,
so ids are passed with `|json_encode`: a MongoDB id such as `5e1234…` would otherwise become a
number.

Other generic controllers: `admin--modal-form` (AJAX forms: `target`, `successMessage`,
`errorMessage` when the response is the form again, `failMessage`, `titleError` (show the
`<title>` of the error page), `refresh`, `modal` (empty for forms outside a modal),
`disableSubmit`, and `created` (`{key, list, listUrl, preview, showUrl}`: reload the list and the
preview of the created element); it dispatches `admin--modal-form:saved` after saving),
`admin--meta-form` (metadata forms: the "press OK" reminder, with `prefix` or `always`, the cancel
button, the keyword inputs, whose suggestion indexes it creates on demand, and for the multimedia
objects the recording/publication date warning, `dates`, and the title of the links panel,
`title`), `admin--filter-form` (filters: `url`, `target`, `letters`, with the `reset` and
`letter` actions) and `admin--batch` (the "Actions on selected items" select: `name`, `plural`,
`preview`, `urls`, `messages`). Messages are translated in Twig and passed as values or params,
so the controllers carry no translations. Zone controllers: `admin--group-picker` (groups of a
user), `admin--group-info` (group deletion), `admin--person-picker` (person search of a
relation), `admin--permission-profiles`, `admin--tag-tree`, `admin--file-tree` (inbox
explorer of the server tab), `admin--changepub` (status of the multimedia objects of a series) and
`admin--event-tabs` (list and calendar tabs of the legacy events), `admin--pic-list` (updates the
preview thumbnails after reloading a picture list), `admin--pic-generator` (thumbnail generation
from a frame of the video), `admin--job-status` (refreshes the status of the multimedia objects with
running jobs every 15 seconds), `admin--pic-sync` (reloads the thumbnails when a refreshed status
reports a different number of pictures), `admin--modal-cleanup` (removes the player of the
preview modal when it closes), `admin--external-player` (preview and save of the external player
modals), `admin--track-list` (reloads the links after a track change and captures frames),
`admin--job-priority` (priority of a pending job), `admin--unesco` (UNESCO catalogue), `admin--live-event` (live events), `admin--places` (places and precincts), `admin--slider` (order of the slider manager), `admin--dashboard` (activity chart of the dashboard), `admin--series-styles` (series styles manager), `admin--playlist-mms`, `admin--playlist-modal` and `admin--playlist-add` (playlists), `admin--group-lists` (the available and selected group lists of the admin groups and the
broadcast groups: `name`, `showCodes`, `editable`, `message`; it dispatches `admin--group-lists:changed`
after a move and rebuilds the lists on `admin--group-lists:load`), `admin--mm-groups` (save and reset
of the admin groups, owner deletion and the warning shown when the user would lose access),
`admin--broadcast` (broadcast profile modals: type, password checks and generator, groups, reset and
the multimedia objects of a series) and `admin--tag-picker` (tag tab of the multimedia object editor: `mm`, `addUrl`,
`deleteUrl`, `childrenUrl`, `searchUrl` and `reloadUrl` with a `__tag__` placeholder where needed,
`codes`, and `refresh`, the blocks reloaded after adding or removing a tag). `admin--batch` now covers every option of the multimedia object list:
deleting, announcing, the sort options (each with its `data-url`), cutting, pasting and adding to a
playlist.

Migrated zones: roles, series types, live channels, legacy events, users, groups, people and
person relations, permission profiles, tags, links, the inbox explorer, series, playlists, the
head and tail manager, pictures, the i18n fields of the form theme and the multimedia object list
(rows, pager, batch actions, row menu, archive, status and preview), its metadata, publication,
metadata sync, LMS, properties, head and tail and external player forms, its tag tab and links block,
also in the UNESCO catalogue, its owners and admin groups, and the broadcast profile modals of
multimedia objects and series. `@PumukitNewAdmin/MultimediaObject/updatebroadcast.js.twig` is removed:
the `admin--broadcast` controller handles both broadcast modals. The tracks, jobs and materials of the
editor use `admin--actions`, `admin--modal-form` and two zone controllers, `admin--track-list` and
`admin--job-priority`. The UNESCO catalogue no longer defines `window.update_list()` to reload its list
after a track is deleted: its `#list_mms` carries `data-refresh-url`, and `pumukit:check:assets`
reports the projects that still define the hook.

The UNESCO catalogue (list, folder menu, drag and drop of tags and rows, preview, search, custom
fields, batch actions, row menu, metadata and publication forms) uses the `admin--unesco` controller.
Every fragment that the catalogue reloads (`list`, `menuTags`, `show`, `search_view`,
`custom_fields`) starts its own instance through `@PumukitNewAdmin/UNESCO/_controller.html.twig`,
so the fragments keep working inside a project override of `@PumukitNewAdmin/UNESCO/index.html.twig`.
The index no longer defines `submit_delete_mms_unesco()`, `submit_clone_mms_unesco()` nor the
keyword indexes: `pumukit:check:assets` reports the overrides that still define them. The metadata
form uses `admin--meta-form` and `admin--modal-form` like the multimedia object one, with a `block`
flag in its `dates` value so a publication date before the recording date stops the save. An
override of `@PumukitNewAdmin/UNESCO/updatemeta.html.twig` no longer gets the "Press OK" notice
from `pumukit.js.twig`: copy the attributes of the bundle template.

The live events zone (list, filters, batch delete, row menu, event form with the channel search,
sessions and the series modal) uses the `admin--live-event` controller, started through
`@PumukitNewAdmin/LiveEvent/_controller.html.twig` on the index, on the list (which also loads the
selected event) and on the series modal. Its event form uses `admin--meta-form` for the "Press OK"
notice and the cancel button, and the index no longer defines the keyword indexes. The
`event_option`, `sort_field`, `filterSearchEvents`, `resetSession`, `edit_session`, `clone_session` and
`delete_session` classes are kept for styling only.

The places and precincts zone (lists, select all, preview, deletion and batch deletion) uses the
`admin--places` controller, started by `@PumukitNewAdmin/Place/index.html.twig`, and its create and
update modals use `admin--modal-form`. The `load_children`, `data_children`, `delete_parent_tag` and
`delete_children_tag` classes are kept for styling only. `admin--modal-form` now shows the `status`
of a JSON error response instead of the raw body. The slider manager uses `admin--slider`
(drag and drop of the `.slider-item` rows and the save button, still `#save-slider-order`). The
charts of the dashboard use `admin--dashboard`, which imports Chart.js from the importmap with
category scales (the importmap has no date adapter), so
`bundles/pumukitnewadmin/vendor/chartjs/chart.umd.min.js` and
`bundles/pumukitnewadmin/vendor/chartjs/chartjs-adapter-date-fns.bundle.min.js` are removed. The unused
`bundles/pumukitnewadmin/vendor/chartjs/chartjs-plugin-zoom.min.js` is removed, and so are
`bundles/pumukitnewadmin/js/jquery.nouislider.min.js` (noUiSlider) and
`bundles/pumukitnewadmin/css/jquery.dropdown.css`, which the admin layout loaded but no page used. The unminified copies
`bundles/pumukitwebtv/css/bootstrap.css`, `bundles/pumukitwebtv/css/font-awesome.css` and
`bundles/pumukitnewadmin/lib/fontawesome/css/all.css`, which no template loaded, are removed: use their `.min.css` files. The series styles
manager uses `admin--series-styles`, started by `@PumukitNewAdmin/SeriesStyle/crud.html.twig`; the
`show_style` and `delete_style` classes are kept for styling only. The profile page submits its form
without JavaScript: the `pumukitnewadmin_profile_user_check_user` route and
`UserProfileController::checkEmailToUseOnUser()` are removed.

The multimedia objects of a playlist use `admin--playlist-mms` (row selection, preview, removal and
batch removal; the list starts its own instance, so it keeps working inside a project override of
`@PumukitNewAdmin/PlaylistMultimediaObject/index.html.twig`), the "Add" modal uses
`admin--playlist-modal` (search, URL, my videos with infinite scroll and the add button, still
`#add_videos_modal_button`) and the "Add to playlist" modal of the multimedia object list uses
`admin--playlist-add`. `@PumukitNewAdmin/UNESCO/listtagsajax.html.twig` is removed: the UNESCO tag
tab uses `@PumukitNewAdmin/MultimediaObject/listtagsajax.html.twig`, and the `admin--tag-picker`
controller of the tab decides which blocks to refresh after adding or removing a tag. `@PumukitNewAdmin/Playlist/listthjs.html.twig` and the unused
`@PumukitNewAdmin/Playlist/changepub.html.twig` are removed, and so is
`@PumukitNewAdmin/LiveEvent/create.html.twig`, which pointed to a route that no longer exists.
The picture upload actions (series, playlists, multimedia objects and legacy events) and the
material upload action always answer JSON (`{uploaded, message}`), so
`@PumukitNewAdmin/Pic/upload.html.twig`, `@PumukitNewAdmin/Pic/upload_event.html.twig` and
`@PumukitNewAdmin/Material/upload.html.twig`, the old iframe responses, are removed, together
with the hidden banner URL form of `@PumukitNewAdmin/Pic/banner.html.twig` and its
`test_form_url()` and `test_url_generation()` functions. Unused code removed as well:
`@PumukitNewAdmin/MultimediaObject/acordeon.html.twig`, the `update_preview_mms()` function defined
by the multimedia object and playlist indexes, the `procesaReset()` function, and the handlers of the multimedia object "New" button
and of the multimedia object template "Cancel" button, which no template rendered. The multimedia object social form, hidden
since 5.x, is removed with its `pumukitnewadmin_mms_update_social` route,
`MultimediaObjectController::updatesocialAction()` and `@PumukitNewAdmin/MultimediaObject/updatesocial.html.twig`. The series, playlist and multimedia object
indexes no longer define the `serieskeywords` and `mmobjkeywords` globals. The keyword fields
(`input.materialtags` of the metadata forms, listed in the `keywords` value of `admin--meta-form`)
no longer use materialize-tags, typeahead.js and Bloodhound, which are removed: a small module of
the bundle (`@pumukit/new-admin/keywords.js`) builds the same `.materialize-tags` box with `.chip`
tags and suggests the keywords of the API. Its styles are in
`bundles/pumukitnewadmin/css/keywords.css`.

hinclude.js is removed from the backoffice layout. The previews that the indexes load after the
page (multimedia objects, series, legacy events, people, live events, playlists and the multimedia
objects of a playlist) use the `admin--include` controller instead of `render_hinclude()`:

```twig
<div data-controller="admin--include" data-admin--include-src-value="{{ url('pumukitnewadmin_mms_show', {'id': id}) }}"></div>
```

`pumukit:check:assets` reports the `render_hinclude()` calls left in the project templates.

Select2 is removed. The channel search of the live event form (`#live_channel_select`) is a text
field with the `SuggestInput` autocomplete of `admin--live-event`, like the series search next to it:
it lists every channel on focus, filters as you type and still fills `#live_channel_input_id` and
`#live_channel_input_name`; emptying it after choosing a channel clears the choice.
`@PumukitNewAdmin/LiveEvent/select2js.html.twig`, `bundles/pumukitnewadmin/js/select2/` and
`bundles/pumukitnewadmin/css/select2.min.css` are removed, and the `channel` message of
`@PumukitNewAdmin/LiveEvent/_controller.html.twig` is now the placeholder of the field.

Removed global
functions, reported by `pumukit:check:assets` (also when called as `window.…`):

| Removed | Replacement |
|---|---|
| `submit_delete_role()`, `submit_delete_user()`, `submit_delete_live()`, `submit_delete_seriestype()`, `submit_delete_event()`, `submit_delete_permissionprofile()`, `submit_delete_person()`, `submit_delete_link()`, `delete_eventpic()` | `admin--actions#remove` |
| `click_fila_url()` | `admin--actions#preview` |
| `filter_click_letter()` | `admin--filter-form#letter` |
| `submit_delete_group()`, `can_be_deleted()`, `delete_user_from_group()`, `delete_multimediaobject_from_group()`, `delete_embeddedbroadcast_from_group()` | `admin--group-info` |
| `add_group_user()`, `delete_group_user()` | `admin--group-picker` |
| `processPermissionProfilesOk()`, `checkScope()` | `admin--permission-profiles` |
| `toggle_section_tag()`, `tag_delete()` | `admin--tag-tree` |
| `fileServerTree()`, `fileServerSelect()` | `admin--file-tree` |
| `submit_generate_magic_series()` | `admin--actions#magicUrl` |
| `submit_clone_series()` | `admin--actions#load` |
| `submit_delete_series()`, `submit_delete_playlist()` | `admin--actions#remove` |
| `click_checkbox_changepub_all()`, `check_checkbox_all()`, `check_checkbox_channel_all()`, `click_checkbox_channel_changepub_all()`, `submit_series_change_pub()` | `admin--changepub` |
| `init_series_materialtags()` | `admin--meta-form` |
| `up_seriespic()`, `down_seriespic()`, `up_playlistpic()`, `down_playlistpic()`, `up_mmspic()`, `down_mmspic()`, `change_pics_page()` | `admin--actions#load` |
| `delete_seriespic()`, `delete_playlistpic()`, `delete_mmspic()`, `delete_eventspic()` | `admin--actions#remove` |
| `update_mmspic()`, `update_seriespic()`, `update_playlistpic()` | `admin--pic-list` |
| `pmk_translate()` | `admin--actions#translate` |
| `submit_delete_mms()` | `admin--actions#remove` |
| `submit_clone_mms()` | `admin--actions#load` |
| `change_select()`, `get_batch_delete_route()`, `sort_optiont_change()` | `admin--batch` |
| `update_mms_status_wrapper()` | `admin--job-status` |
| `check_for_new_pics()`, `upload_mmspic()` | `admin--pic-sync` |
| `checkRecordDateAndPublicDate()` | the `dates` value of `admin--meta-form` |
| `procesaReset()` | nothing |
| `select_tree_tag()`, `toggle_tree_tag()`, `add_tree_tag()`, `del_tree_tag()`, `add_tree_tag_unesco()`, `del_tree_tag_unesco()`, `search_tags_wrapper()`, `search_tags()`, `reset_tags()`, `inc_num_mm()`, `create_element_in_select()` | `admin--tag-picker` |
| `submit_generate_magic_mms()` | `admin--actions#magicUrl` |
| `sync_tags_mms()` | `admin--actions#request` with a `confirm` param |
| `submit_delete_material()`, `submit_delete_track()`, `update_list()` | `admin--actions#remove` |
| `getAllDnDChecked()`, `dragEndCheckbox()`, `dragItem()`, `dragEnd()`, `dropTagItem()`, `updateMultimediaObjectByTag()`, `dropItem()`, `updateMultimediaObject()` | `admin--unesco` |
| `procesaOk()` | `admin--meta-form#hideRemember` |
| `dblclick_fila_url()` | `admin--actions#navigate` |
| `click_fila_edit_urls()` | `admin--actions#edit` |
| `dblclick_preview_mm()` | `admin--actions#openModal` |
| `click_checkbox_all()` | `admin--actions#checkAll` |
| `playlist_mms_options()`, `mm_delete_selected()`, `delete_mm()` | `admin--playlist-mms` |
| `init_mmobj_materialtags()` | the `keywords` value of `admin--meta-form` |
| `Bloodhound` | the `keywords` value of `admin--meta-form` |
| `extract_pic()` | `admin--track-list#extractPic` |
| `toggleHide()` | `admin--actions#load` |
| `select_group()`, `add_group()`, `delete_group()`, `create_element_group_in_add_groups()`, `create_element_group_in_delete_groups()`, `delete_element_group_from_select()` | `admin--group-lists` |
| `submit_delete_person_relation()`, `is_last_user_relation_to_admin_mm()`, `pumukitnewadmin_mm_updategroup_submit()`, `pumukitnewadmin_mm_updategroup_reset()` | `admin--mm-groups` |
| `init_tabs()` | `admin--tabs` |
| `check_pass()`, `generate_random_password()`, `can_enable_broadcast_groups_save_button()`, `can_enable_broadcast_password_save_button()`, `get_submit_button_id()`, `click_checkbox_change_pub_mms()`, `click_changepub_selection()` | `admin--broadcast` |

Names, titles and file names are now rendered as text: the group picker, the group deletion
button, the person suggestions and the inbox explorer used to build HTML and inline handlers by
concatenating them.

## Encoder profile commands

The variables of the `bat` command of the encoder profiles (`{{input}}`, `{{output}}`,
`{{tracks.*}}`, `{{properties.*}}`, `{{tmpfileN}}`…) are escaped for a double-quoted shell string:
`\`, `"`, `$` and `` ` `` get a backslash. Before, they were escaped as HTML, so a file whose name
contained `&` or `"` failed to transcode (`&amp;`, `&quot;` reached ffmpeg), and a name with
`$(…)` or backticks ran that command on the encoder. Every variable of a project profile must be
inside double quotes, as in the core profiles:

```yaml
bat: ffmpeg -y -i "{{input}}" -c:v libx264 "{{output}}"
```

## Messenger and RabbitMQ

The core installs Symfony Messenger with the AMQP transport (`symfony/amqp-messenger`, PHP
extension `amqp` in the Docker image) and ships a RabbitMQ service. It defines no transport,
queue or failure transport of its own: each bundle declares the ones it needs, with the
`MESSENGER_TRANSPORT_DSN` connection, from the `prepend()` method of its extension:

```php
$container->prependExtensionConfig('framework', [
    'messenger' => [
        'transports' => [
            'youtube_upload' => [
                'dsn' => '%env(MESSENGER_TRANSPORT_DSN)%',
                'options' => ['exchange' => ['name' => 'youtube'], 'queues' => ['youtube_upload' => []]],
            ],
        ],
        'routing' => ['Pumukit\YoutubeBundle\Message\UploadVideoMessage' => 'youtube_upload'],
    ],
]);
```

Prefix the transport, exchange and queue names with the bundle so that they do not collide.
The test environment sets `MESSENGER_TRANSPORT_DSN=in-memory://`, so the transports of a bundle
need no RabbitMQ in its tests. A bundle that consumes its queues adds a worker service to the
deployment compose file, with the PuMuKIT image and
`bin/console messenger:consume <transports> --time-limit=3600`.


Every file upload goes through Uppy. PuMuKIT 6.0 moves from the Uppy 1 global bundle
(`bundles/pumukitcore/js/uppy.min.js`, `window.Uppy`) to Uppy 6 (`@uppy/core` 6.2.0,
`@uppy/dashboard` 6.0.1, `@uppy/tus` 6.0.0, `@uppy/xhr-upload` 6.0.1, `@uppy/locales` 5.3.0) loaded
from the importmap by a single generic controller, `core--uploader`. Pages that upload files no
longer load any Uppy script or stylesheet: the controller adds the Uppy styles itself.

An upload point is declared with the `uploader_attributes()` Twig function on the element that
holds the uploader. The defaults come from the `pumukit` inbox configuration (`minFileSize`,
`maxFileSize`, `maxNumberOfFiles`, `inboxUploadURL`, `inboxUploadLIMIT`,
`inboxOverridePatchMethod`, `inboxProgressBarColor`, `inboxDebug`) and the allowed types from
`MediaMimeTypeUtils`; pass only what changes:

```twig
{# Drag and drop to the inbox through TUS #}
<div id="my_dropzone"></div>
<div hidden {{ uploader_attributes({
    endpoint: inbox_upload_url() ~ '?' ~ {series: series.id}|url_encode,
    meta: {series: series.id},
    renameFiles: true,
    dashboard: {target: '#my_dropzone'},
    import: {url: path('inbox_auto_import')}
}) }}></div>

{# One file posted with the fields of a form (XHR) #}
<form {{ uploader_attributes({
    transport: 'xhr',
    endpoint: path('my_upload_route'),
    formMeta: true,
    restrictions: {maxNumberOfFiles: 1, allowedFileTypes: uploader_allowed_types(multimediaObject)},
    xhr: {fieldName: 'resource[]'},
    dashboard: {height: 260, hideUploadButton: true, note: 'Max. 2GB'}
}) }}>
    <div data-core--uploader-target="dashboard"></div>
    <button type="submit" data-core--uploader-target="submit" data-action="core--uploader#upload" disabled>OK</button>
</form>
```

| Option | Default | Meaning |
|---|---|---|
| `transport` | `tus` | `tus` (resumable, inbox) or `xhr` (multipart POST) |
| `endpoint` | `inboxUploadURL` for TUS | upload URL |
| `autoProceed` | `true` for TUS, `false` for XHR | start uploading as soon as a file is added |
| `restrictions` | inbox sizes, `maxNumberOfFiles`, all allowed types | Uppy restrictions; only the given keys are replaced |
| `meta` | `{}` | metadata sent with every file |
| `renameFiles` | `false` | prefix the name with a timestamp and replace non-word characters (inbox uploads) |
| `formMeta` | `false` | send the fields of the enclosing form as metadata when uploading |
| `dashboard` | `null` | Uppy Dashboard options (`height`, `note`, `hideUploadButton`…); `target` is a selector, otherwise the `dashboard` target is used |
| `import` | `null` | `{url, params, profileSelector, sendUuid}`: POST to `url` after each TUS upload with `fileName`, `series`, `profile` and `params` |
| `tus` | inbox `overridePatchMethod` and `limit` | TUS options |
| `xhr` | `fieldName: files[]`, `limit: 10`, `timeout: 1800000`, `Accept: application/json` header | XHR options; the default `maxFileSize` is also capped by PHP `upload_max_filesize` and `post_max_size` |

Every upload shows the Uppy Dashboard (drag and drop, preview, progress). The controller enables
its `submit` targets when a file is selected. Actions: `core--uploader#upload` and `#remove`.
`uploader_allowed_types(multimediaObject)` returns the types of its media (audio, video, image,
document), or every allowed type when the object has no type yet. The inbox progress bar colour
(`inboxProgressBarColor`) applies to every Dashboard.

The backoffice forms that upload a file (thumbnails of videos, series, playlists and events, the
live event poster, the series banner, materials and tracks) use the Dashboard and the
`admin--upload-form` controller of NewAdminBundle, which handles the submit button, the URL
alternative, the validations, the refresh of the lists and the notices. Their upload actions
answer `{uploaded, message}` as JSON when the request accepts `application/json`, and keep the
HTML response of the hidden iframe otherwise, so overrides that still post to `iframeUpload` keep
working until they are migrated.

The controller dispatches DOM events instead of exposing globals: `core--uploader:added`,
`:removed`, `:success`, `:complete`, `:imported`, and the cancelable `:error`,
`:restriction-failed`, `:failed` (XHR response with `uploaded: failed`) and `:import-failed`.
`event.detail` carries the file and the server response; call `preventDefault()` on a cancelable
event to replace the default notice (Dashboard info or `alert()`). After an XHR upload the
response keys keep their meaning: `endPage` redirects and `mmId` is posted to the parent window
as `{mmId}` (LMS plugins).

Changes to take into account:

- `@PumukitCore/Upload/uppy_xhr.js.twig`, `common.js.twig` and `uppy_languages.html.twig` are
  removed, as well as the `Uppy`, `convertSizeToBytes`, `sendInboxXHRImportRequest`,
  `show_file_added` and `file_data_clear` globals, which `pumukit:check:assets` reports, and the
  `uppy` variable that `uppy_xhr.js.twig` created.
- `bundles/pumukitcore/js/uppy.min.js`, `js/uppy-locales/*`, `css/uppy.min.css` and
  `css/uppy_custom.css` are removed. Overrides of the Uppy 1 CSS classes (`.uppy-FileInput-*`)
  no longer apply. `@PumukitCore/Upload/uppy_input.html.twig` now holds the Dashboard target of
  the track form.
- `@PumukitCore/Upload/uppy_tus.html.twig` keeps its name and variables (`folder` or `series`,
  `inboxUploadURL`, and `username`, `email`, `hash` in naked mode).
- The TUS server validates the same `name`/`type` metadata that tus-php stores, and rejects
  uploads whose `name` and `filename` (or `type` and `filetype`) differ or repeat a key.

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

## Configuration defaults

PuMuKIT 6.0 enables its optional features by default; projects disable explicitly the ones
they do not use. These options change their default value from `false` to `true`:

| Option | Feature |
|---|---|
| `pumukit_new_admin.enable_playlist` | Playlists menu in the backoffice |
| `pumukit_new_admin.show_menu_place_and_precinct` | Places and precincts menu in the backoffice |
| `pumukit_base_live_player.advance_live_event_autocomplete_series` | Button to fill the series with the live event data |
| `pumukit_core.showBackofficeButtonInInbox` | Button to the backoffice in the simplified inbox |
| `menu.show_stats` (parameter) | Statistics block in the portal menu |
| `pumukit.warning_on_unpublished` (parameter) | Warning in the backoffice on unpublished or unplayable multimedia objects |
| `pumukit.inbox_depth` (parameter) | Inbox folder file counts include subfolders |

`pumukit_core.full_magic_url` was never read: the WebTV controllers received a broken
parameter reference that behaved as `true`. It now works and keeps its default of `false`,
so access through a magic URL no longer lists the hidden multimedia objects of the series
unless a project sets it to `true`.

With `full_magic_url: true`, the "Content from same Series" block of a video page lists
the hidden multimedia objects only on a magic URL page or when the visitor comes from the
magic URL of the series. It no longer trusts a `Referer` header that merely contains `magic`,
so going from a magic URL page to a published video of the series shows only the published
ones.

## Output escaping

The core no longer prints user data as HTML. Projects that override these templates or reuse
these pieces must follow the same rules:

- **Series header, footer and subseries titles** are HTML written in the backoffice. They are now
  printed through `symfony/html-sanitizer` with the `pumukit.series_html` sanitizer, declared by
  the WebTVBundle: formatting, lists, tables, images and `http`, `https`, `mailto` and relative
  links are kept, with their `class`, `style` and `target` attributes; scripts, event handlers,
  iframes, forms and `javascript:` URLs are removed. Overridden templates must print them with
  `|sanitize_html('pumukit.series_html')` instead of `|raw`.
- **URLs written by users** (links, materials, person webs) are printed with the new `safe_url`
  Twig filter, which turns any scheme other than `http`, `https` and `mailto` into `#`. Use it in
  every `href` of an overridden template. The link and person web fields of the backoffice only
  accept `http` and `https` URLs: editing an existing link with another scheme (for example
  `ftp://`) asks to change it.
- **Backoffice form labels** are escaped. The `form_label` block of
  `@PumukitNewAdmin/Themes/fields.html.twig` no longer decodes HTML entities; a form type whose
  label contains HTML must set `'label_html' => true` (the copyright and license labels with their
  information link already do).
- **UNESCO catalogue**: `render_object_field()` returns escaped HTML. Templates print it with
  `|raw` as before, and with `|striptags|unescape` inside attributes.
- **Plain text fields** are escaped: the live event messages ("already held", "not yet held") and
  the session notes, which now keep their line breaks with `nl2br`. Projects that relied on
  writing HTML in them must move that HTML to the template.
- **Uploads**: materials and pictures with the extensions `xht`, `shtml`, `svgz`, `xml`, `xsl`,
  `xslt`, `mht`, `mhtml` and `hta` are rejected, as `html`, `htm`, `xhtml`, `svg` and `swf`
  already were, and `/uploads` is served with `X-Content-Type-Options: nosniff`. Projects with
  their own NGINX configuration should add that header to their uploads location.
- The core test `UnescapedOutputTest` fails when a template adds `|raw`, `|unescape` or
  `autoescape false`, a Twig extension adds `is_safe`, or a Stimulus controller inserts HTML with
  `.html(...)`, `innerHTML` or `insertAdjacentHTML`, outside its reviewed list.

## Removed metadata translation

The metadata translation button of the i18n fields was never implemented and is removed:

- `Pumukit\NewAdminBundle\Controller\TranslateController`, the
  `pumukitnewadmin_metadata_translate` route and the `pumukit_new_admin.metadata_translators`
  parameter no longer exist.
- Projects with their own `config/packages/legacy_aliases.yaml` must remove the
  `pumukitnewadmin.translatecontroller` alias, otherwise the container fails to compile.
- `AbstractI18nType` (and `TextI18nType`, `TextI18nAdvanceType`, `TextareaI18nType`) take only
  `$locales` in the constructor, and their views no longer get the `translators` variable.

The `pumukitnewadmin_seriespic_index` and `pumukitnewadmin_mmspic_index` routes are removed too:
they pointed to actions that never existed.

## Removed backoffice routes

- The `pumukitnewadmin_{series,mms,seriestype,role,person,live}_copy` routes were not used by the
  backoffice and are removed, with `AdminController::copyAction()` and the `cloneResource()`
  methods of `Person`, `Role` and `Live`.
- `pumukitnewadmin_seriespic_show`, `pumukitnewadmin_mmspic_show`, `pumukitnewadmin_track_info`
  and `pumukitnewadmin_track_autocomplete` pointed to actions that never existed.
- `ConfController::PAELLA_*_CONFIG_FOLDER` constants are removed with the unused method that read
  them.

## Statistics

The statistics pages of StatsUIBundle are replaced by a single audience panel:

- The panel is served at `/admin/stats` (route `pumukit_stats_index`) and the backoffice menu has
  a single "Stats" entry. The `pumukit_stats_series_index` and `pumukit_stats_mmobj_index` routes
  are removed; the detail routes `pumukit_stats_series_index_id` and
  `pumukit_stats_mmobj_index_id` keep their paths. Users with the personal scope only see the
  statistics of their own multimedia objects (owner or groups), also in the detail pages.
- The templates `@PumukitStatsUI/Search/*`, `@PumukitStatsUI/MostViewed/*` and
  `@PumukitStatsUI/Views/*` and the `stats-ui--stats` controller are removed.
- The panel reads a new read-only REST API under `/api/v1/stats/` (`summary`, `timeline`,
  `sources`, `devices`, `heatmap`, `top-videos`, `top-series`), protected by `ROLE_ACCESS_STATS`
  and filtered by the user's scope. The previous `/api/media/*` endpoints are kept.
- The API reads three new collections that every view updates: `ViewsDailyStats` (one document
  per day and multimedia object), `ViewsDailyTotals` (one document per day) and
  `ViewsDailyVisitor` (unique-view keys, expired after three days). Run
  `pumukit:stats:rollup` once to build them from the existing `ViewsLog` (see the deployment
  guide).
- `ViewsLog` stores the IP truncated to its /24 (IPv4) or /48 (IPv6) network and a daily
  `visitor` hash used to count unique views. Existing documents keep the full IP.
- `Pumukit\StatsBundle\EventListener\Log` takes three new constructor arguments:
  `VisitorAnonymizer`, `ViewsDailyStatsService` and a `LoggerInterface`.

## Deprecated API

The REST API under `/api/v1/` (API Platform) replaces the previous endpoints under `/api/`, which
keep working in 6.x and will be removed in 7.0. The core only uses `/api/v1/`.

- Every call to a previous endpoint answers with a `Deprecation` header and, when there is one, a
  `Link: <...>; rel="successor-version"` header with the new endpoint.
- Every call is logged in `var/log/legacy_api.log` (monolog channel `legacy_api`, 30 days) with
  the route, the user, the IP truncated to its network, the user agent and the referer, so that
  the installations can find out which integrations still use them before upgrading to 7.0.

| Previous endpoint | Replacement |
|---|---|
| `/api/media/mmobj`, `/api/media/series` | `/api/v1/multimedia_objects`, `/api/v1/series` |
| `/api/media/stats` | `/api/v1/dashboard/summary` |
| `/api/media/mmobj/stats`, `*/recorded`, `*/num_recorded`, `/api/media/hours/*` | `/api/v1/dashboard/activity` |
| `/api/media/views`, `/api/media/views/mmobj`, `/api/media/views/series` | `/api/v1/stats/timeline` (with `multimediaObject` or `series`) |
| `/api/media/mmobj/most_viewed`, `/api/media/series/most_viewed` | `/api/v1/stats/top-videos`, `/api/v1/stats/top-series` |
| `/api/keywords/mmobj`, `/api/keywords/series` | `/api/v1/keywords/multimedia_objects`, `/api/v1/keywords/series` |
| `/api/encoder/jobs` | `/api/v1/dashboard/jobs` (counts only) |
| `/api/media/series/recorded`, `*/num_recorded`, `*/published`, `/api/media/series/user`, `/api/media/live`, `/api/media/locales`, `/api/live/*`, `/api/encoder/cpus`, `/api/encoder/profiles`, `/api/user/users` | None yet |

The admin keyword autocomplete reads `/api/v1/keywords/*`, which answers
`{"items": [{"keyword": "...", "count": 3}]}`. Overridden templates that still point to
`pumukit_schema_apikeywords_*` keep working through the deprecated endpoints.

## Backoffice requests that change data

Backoffice routes that create, delete, clone, reorder or otherwise change data no longer accept
`GET`; they answer `405 Method Not Allowed` to it. Overridden templates and bundle JavaScript must
send `POST` (or `DELETE` where the route already accepted it):

| Routes | Now |
|---|---|
| `pumukitnewadmin_{mms,role,playlistmms}_{up,down,top,bottom}`, `pumukitnewadmin_person_{up,down}` | `PUT`, `POST`. Links that navigate use `data-action="click->admin--actions#post"`, which submits a `POST` form to the link URL |
| `pumukitnewadmin_track_pic`, `pumukitnewadmin_track_retranscode` | `PUT`, `POST` |
| `pumukitnewadmin_playlistmms_batch_delete` | `POST`, `DELETE` |
| `pumukitnewadmin_{mms,series}_invert_announce`, `pumukitnewadmin_series_create`, `pumukitnewadmin_mms_create`, `pumukitnewadmin_person_link`, `pumukitnewadmin_playlistmms_batch_add` | `POST` |
| `pumukit_newadmin_series_styles_{create,edit,delete}` (`create` now reads `name` from the request body), `pumukitnewadmin_places_delete`, `pumukit_newadmin_head_and_tail_manager_remove_item` | `POST` |
| `pumukitnewadmin_unesco_{add_tag,clone,delete,delete_tag,options_list}` | `POST` |
| `pumukit_new_admin_live_event_{create,clone_session,delete_selected,session_delete,options}`, `pumukitnewadmin_live_event_edit_series`, `pumukit_new_admin_autocomplete_series_with_event_data` | `POST` |
| `pumukit_encoder_cpu_maintenance_switch`, `wizard_upload_from_server`, `inbox_auto_import` | `POST` |

The co-owner rejection link of the owner email (`pumukit_multimedia_object_reject_co_owner`)
keeps working with `GET`, but it now shows a confirmation page and rejects on `POST`.

Requests to `/admin` and `/chat` with a method other than `GET`, `HEAD`, `OPTIONS` or `TRACE` are
rejected with `403` when the browser marks them as coming from another site (`Sec-Fetch-Site`
other than `same-origin` or `none`, or an `Origin` header for another host). Requests without
those headers, such as `curl` or server-to-server calls, are not affected. Integrations that post
to the backoffice from a page on another domain must call an API instead.

## Live chat

- `pumukit_live_chat_post` and `pumukit_live_chat_basic_post` only accept `POST`, and answer
  `404` when `pumukit_live.chat.enable` is `false` (the default): before, anyone could store
  messages even with the chat disabled.
- Each IP address can post 10 messages per minute (`429` above that). The limit is the
  `chat_message` rate limiter declared by the WebTVBundle; a project can change it under
  `framework.rate_limiter.chat_message`. Until the proxy is configured as in
  `doc/upgrade/from5.1to6.0.md`, the address can be spoofed with `X-Forwarded-For`.
- Messages keep a SHA-256 hash of the author's session instead of reading a `PHPSESSID` cookie that
  PuMuKIT never sets (its session is called `pumukit`). Anonymous authors are remembered from the
  first message they post with 6.0.

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

## Backoffice translations

The backoffice form types translate their labels with the injected translator, so they set
`translation_domain: false` and `@PumukitNewAdmin/Themes/fields.html.twig` no longer
translates them a second time. Fields without a label of their own and choice lists whose
labels are translation keys keep the `messages` domain.

- Fields added to these forms from a form extension or a subclass must pass a translated
  `label`, or set `translation_domain: 'messages'`.
- `@PumukitNewAdmin/Misc/panel_title.html.twig` prints `page_title` as is: pass it
  translated (`'My profile'|trans`). The same applies to `titleList` in
  `@PumukitNewAdmin/HeadAndTail/_list.html.twig`.
- The track language selector only translates the PuMuKIT addon languages
  (`CustomLanguageType::$addonLanguages`). The names returned by `Languages::getNames()`
  are already localised.

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
