<?php

declare(strict_types=1);

namespace Pumukit\WebTVBundle\Twig;

use MongoDB\BSON\UTCDateTime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MongoDateExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('convertMongoDate', [$this, 'convertMongoDateFilter']),
        ];
    }

    public function convertMongoDateFilter(UTCDateTime $mongoDate): string
    {
        return $mongoDate->toDateTime()->format('U');
    }
}
