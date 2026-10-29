<?php

declare(strict_types=1);

namespace Pumukit\StatsBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Attribute as MongoDB;

#[MongoDB\Document(collection: 'ViewsDailyTotals')]
#[MongoDB\Index(keys: ['date' => 1], options: ['unique' => true])]
class ViewsDailyTotals
{
    #[MongoDB\Id]
    private $id;

    #[MongoDB\Field(type: 'date')]
    private $date;

    #[MongoDB\Field(type: 'int')]
    private $views = 0;

    #[MongoDB\Field(type: 'int')]
    private $uniqueViews = 0;

    #[MongoDB\Field(type: 'hash')]
    private $sources = [];

    #[MongoDB\Field(type: 'hash')]
    private $devices = [];

    #[MongoDB\Field(type: 'hash')]
    private $hours = [];
}
