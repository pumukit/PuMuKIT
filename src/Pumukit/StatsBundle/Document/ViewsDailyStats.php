<?php

declare(strict_types=1);

namespace Pumukit\StatsBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Attribute as MongoDB;

#[MongoDB\Document(collection: 'ViewsDailyStats')]
#[MongoDB\Index(keys: ['date' => 1, 'multimediaObject' => 1], options: ['unique' => true])]
#[MongoDB\Index(keys: ['multimediaObject' => 1, 'date' => 1])]
#[MongoDB\Index(keys: ['series' => 1, 'date' => 1])]
class ViewsDailyStats
{
    #[MongoDB\Id]
    private $id;

    #[MongoDB\Field(type: 'date')]
    private $date;

    #[MongoDB\Field(type: 'object_id')]
    private $multimediaObject;

    #[MongoDB\Field(type: 'object_id')]
    private $series;

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
