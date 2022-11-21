<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/config',
        __DIR__ . '/doc',
        __DIR__ . '/public',
        __DIR__ . '/src',
    ]);

    $rectorConfig->import(\Rector\Set\ValueObject\SetList::PHP_52);
    $rectorConfig->import(\Rector\Set\ValueObject\SetList::PHP_53);
    $rectorConfig->import(\Rector\Set\ValueObject\SetList::PHP_54);
    $rectorConfig->import(\Rector\Set\ValueObject\SetList::PHP_55);
    $rectorConfig->import(\Rector\Set\ValueObject\SetList::PHP_56);

    $rectorConfig->import(\Rector\Set\ValueObject\SetList::PHP_70);
    $rectorConfig->import(\Rector\Set\ValueObject\SetList::PHP_71);
    $rectorConfig->import(\Rector\Set\ValueObject\SetList::PHP_72);
};
