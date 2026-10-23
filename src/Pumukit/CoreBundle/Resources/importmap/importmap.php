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
        'version' => '4.5.1',
    ],
    '@kurkle/color' => [
        'version' => '0.3.4',
    ],
];
