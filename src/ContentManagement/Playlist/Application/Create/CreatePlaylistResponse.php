<?php

declare(strict_types=1);

namespace App\ContentManagement\Playlist\Application\Create;

use Pumukit\SchemaBundle\Document\Series;

final class CreatePlaylistResponse
{
    public function __construct(
        public readonly Series $playlist
    ) {}
}
