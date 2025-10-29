<?php

namespace App\Series\Domain\Factory;

use App\User\Domain\ValueObject\UserId;
use Pumukit\SchemaBundle\Document\Series;

interface SeriesFactoryInterface
{
    public function createForUser(UserId $userId, array $title): Series;
}
