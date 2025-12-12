<?php

namespace App\ContentManagement\Playlist\Domain\Factory;

use App\IdentityAndAccess\User\Domain\ValueObject\UserId;
use Pumukit\SchemaBundle\Document\Series;

interface PlaylistFactoryInterface
{
    public function createForUser(UserId $userId, array $title): Series;

    public function update(Series $playlist): Series;
}
