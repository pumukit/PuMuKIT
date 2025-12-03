<?php

declare(strict_types=1);

namespace App\Playlist\UI\Backoffice\Event;

use Pumukit\SchemaBundle\Document\Series;

final class PlaylistFormBuildEvent
{
    public const NAME = 'playlist.form_build';

    private array $fields = [];

    public function __construct(
        private Series $playlist
    ) {}

    public function addField(string $name, array $config): void
    {
        $this->fields[$name] = $config;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getPlaylist(): Series
    {
        return $this->playlist;
    }
}
