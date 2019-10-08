<?php

namespace Pumukit\WebTVBundle\Twig;

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

    /**
     * @throws \Exception
     */
    public function convertMongoDateFilter(\MongoDate $mongoDate): \DateTime
    {
        return new \DateTime('@'.$mongoDate->sec);
    }
}
