<?php

namespace App\Playlist\Domain\Factory;

use App\User\Domain\ValueObject\UserId;
use Pumukit\SchemaBundle\Document\Series;

interface PlaylistFactoryInterface
{
    public function createForUser(UserId $userId, array $title): Series;

    public function update(Series $playlist): Series;
}
