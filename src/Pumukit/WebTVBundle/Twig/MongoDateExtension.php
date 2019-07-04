<?php

namespace Pumukit\WebTVBundle\Twig;

/**
 * MongoDate::toDateTime is only avaliable in PECL mongo >= 1.6.0.
 * PuMuKIT 2.3 must be compatible with PECL mongo 1.4.5 (Ubuntu 14.04).
 * Class MongoDateExtension.
 */
class MongoDateExtension extends \Twig_Extension
{
    /**
     * @return array|\Twig_SimpleFilter[]
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('convertMongoDate', [$this, 'convertMongoDateFilter']),
        ];
    }

    /**
     * @param \MongoDate $mongoDate
     *
     * @throws \Exception
     *
     * @return \DateTime
     */
    public function convertMongoDateFilter(\MongoDate $mongoDate)
    {
        // return $mongoDate->toDateTime()
        return new \DateTime('@'.$mongoDate->sec);
    }
}
