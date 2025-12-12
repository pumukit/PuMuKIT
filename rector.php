<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/src/Analytics',
        __DIR__.'/src/Authentication',
        __DIR__.'/src/ContentManagement',
        __DIR__.'/src/Framework',
        __DIR__.'/src/IdentityAndAccess',
        __DIR__.'/src/MediaProcessing',
        __DIR__.'/src/Notification',
        __DIR__.'/src/Publication',
        __DIR__.'/src/Shared',
        __DIR__.'/src/Streaming',
        __DIR__.'/src/UI',
        __DIR__.'/src/Upload',
    ]);

    $rectorConfig->skip([
        __DIR__.'/src/Pumukit',
    ]);

    $rectorConfig->importNames();
    $rectorConfig->importShortClasses(false);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_82,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
    ]);

};
