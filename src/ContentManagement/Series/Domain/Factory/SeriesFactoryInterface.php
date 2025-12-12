<?php

namespace App\ContentManagement\Series\Domain\Factory;

use App\IdentityAndAccess\User\Domain\ValueObject\UserId;
use Pumukit\SchemaBundle\Document\Series;

interface SeriesFactoryInterface
{
    public function createForUser(UserId $userId, array $title): Series;

    public function update(Series $series): Series;
}
