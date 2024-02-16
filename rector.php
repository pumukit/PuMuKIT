<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/config',
        __DIR__.'/doc',
        __DIR__.'/public',
        __DIR__.'/src',
    ]);

    $rectorConfig->import(LevelSetList::UP_TO_PHP_73);
};
