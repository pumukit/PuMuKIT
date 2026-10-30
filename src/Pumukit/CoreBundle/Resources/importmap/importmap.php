<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'pumukit' => [
        'path' => '@pumukit/core/app.js',
        'entrypoint' => true,
    ],
    '@pumukit/new-admin/backoffice' => [
        'path' => '@pumukit/new-admin/backoffice.js',
        'entrypoint' => true,
    ],
    '@pumukit/new-admin/suggest' => [
        'path' => '@pumukit/new-admin/suggest.js',
    ],
    '@pumukit/translator' => [
        'path' => '@pumukit/core/translator.js',
    ],
    '@pumukit/translations' => [
        'path' => 'var/translations/index.js',
    ],
    '@symfony/stimulus-bundle' => [
        'path' => '@symfony/stimulus-bundle/loader.js',
    ],
    '@symfony/ux-translator' => [
        'path' => '@symfony/ux-translator/translator_controller.js',
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    'intl-messageformat' => [
        'version' => '12.1.4',
    ],
    '@formatjs/fast-memoize' => [
        'version' => '3.1.7',
    ],
    '@formatjs/icu-messageformat-parser' => [
        'version' => '3.5.22',
    ],
    '@formatjs/icu-skeleton-parser' => [
        'version' => '2.1.12',
    ],
    'chart.js' => [
        'version' => '4.6.1',
    ],
    '@kurkle/color' => [
        'version' => '0.3.4',
    ],
    '@uppy/core' => [
        'version' => '6.3.0',
    ],
    '@uppy/dashboard' => [
        'version' => '6.0.2',
    ],
    '@uppy/tus' => [
        'version' => '6.0.0',
    ],
    '@uppy/xhr-upload' => [
        'version' => '6.0.1',
    ],
    '@uppy/locales/lib/es_ES.js' => [
        'version' => '5.3.1',
    ],
    '@uppy/locales/lib/en_US.js' => [
        'version' => '5.3.1',
    ],
    '@uppy/locales/lib/gl_ES.js' => [
        'version' => '5.3.1',
    ],
    '@uppy/core/dist/style.min.css' => [
        'version' => '6.3.0',
        'type' => 'css',
    ],
    '@uppy/dashboard/dist/style.min.css' => [
        'version' => '6.0.2',
        'type' => 'css',
    ],
    'preact/jsx-runtime' => [
        'version' => '10.29.8',
    ],
    'preact/hooks' => [
        'version' => '10.29.8',
    ],
    '@transloadit/prettier-bytes' => [
        'version' => '2.0.0',
    ],
    'mime-match' => [
        'version' => '1.0.2',
    ],
    'preact' => [
        'version' => '10.29.8',
    ],
    'lodash/throttle.js' => [
        'version' => '4.18.1',
    ],
    'namespace-emitter' => [
        'version' => '2.0.1',
    ],
    'nanoid/non-secure' => [
        'version' => '6.0.2',
    ],
    '@uppy/core/provider-views' => [
        'version' => '6.3.0',
    ],
    '@uppy/core/utils' => [
        'version' => '6.2.0',
    ],
    '@uppy/thumbnail-generator' => [
        'version' => '6.0.1',
    ],
    'classnames' => [
        'version' => '2.5.1',
    ],
    '@uppy/core/utils/preact' => [
        'version' => '6.3.0',
    ],
    '@uppy/core/utils/preact/hooks' => [
        'version' => '6.3.0',
    ],
    'shallow-equal' => [
        'version' => '3.1.0',
    ],
    'lodash/debounce.js' => [
        'version' => '4.18.1',
    ],
    'tus-js-client' => [
        'version' => '4.3.1',
    ],
    'wildcard' => [
        'version' => '1.1.2',
    ],
    'preact/compat' => [
        'version' => '10.29.8',
    ],
    'p-retry' => [
        'version' => '8.0.1',
    ],
    'p-queue' => [
        'version' => '9.3.5',
    ],
    'exifr/dist/mini.esm.mjs' => [
        'version' => '7.1.3',
    ],
    'js-base64' => [
        'version' => '3.7.7',
    ],
    'url-parse' => [
        'version' => '1.5.10',
    ],
    'is-network-error' => [
        'version' => '1.3.2',
    ],
    'eventemitter3' => [
        'version' => '5.0.4',
    ],
    'p-timeout' => [
        'version' => '7.0.2',
    ],
    'requires-port' => [
        'version' => '1.0.0',
    ],
    'querystringify' => [
        'version' => '2.2.0',
    ],
    'chartjs-adapter-date-fns' => [
        'version' => '3.0.0',
    ],
    'date-fns' => [
        'version' => '4.4.0',
    ],
];
