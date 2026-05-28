<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/config',
        __DIR__.'/doc',
        __DIR__.'/public',
        __DIR__.'/src',
    ]);
