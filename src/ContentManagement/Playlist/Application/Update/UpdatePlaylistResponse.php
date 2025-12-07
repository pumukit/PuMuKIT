<?php

declare(strict_types=1);

namespace App\ContentManagement\Playlist\Application\Update;

use Pumukit\SchemaBundle\Document\Series;

final class UpdatePlaylistResponse
{
    public function __construct(
        public readonly Series $playlist
    ) {}
}
