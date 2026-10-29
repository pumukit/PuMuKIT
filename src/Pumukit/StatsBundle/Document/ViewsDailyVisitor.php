<?php

declare(strict_types=1);

namespace Pumukit\StatsBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Attribute as MongoDB;

#[MongoDB\Document(collection: 'ViewsDailyVisitor')]
#[MongoDB\Index(keys: ['date' => 1, 'multimediaObject' => 1, 'visitor' => 1], options: ['unique' => true])]
#[MongoDB\Index(keys: ['expiresAt' => 1], options: ['expireAfterSeconds' => 0])]
class ViewsDailyVisitor
{
    #[MongoDB\Id]
    private $id;

    #[MongoDB\Field(type: 'date')]
    private $date;

    #[MongoDB\Field(type: 'object_id')]
    private $multimediaObject;

    #[MongoDB\Field(type: 'string')]
    private $visitor;

    #[MongoDB\Field(type: 'date')]
    private $expiresAt;
}
