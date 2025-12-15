<?php

declare(strict_types=1);

namespace App\Playlist\Infrastructure\Ui\Backoffice\Http\Event;

use Pumukit\SchemaBundle\Document\Series;

final class PlaylistViewTabsEvent
{
    public const NAME = 'playlist.view_tabs';

    private array $tabs = [];

    public function __construct(
        private readonly Series $playlist
    ) {
        $this->addTab('general', 'General', 'fa-solid fa-info-circle', '@Playlist/Views/tabs/general.html.twig', 100);
        $this->addTab('edit', 'Edit', 'fa-solid fa-edit', '@Playlist/Views/tabs/edit.html.twig', 90);
        $this->addTab('objects', 'Objects', 'fa-solid fa-video', '@Playlist/Views/tabs/objects.html.twig', 80);
    }

    public function addTab(string $key, string $label, string $icon, string $template, int $priority = 0): void
    {
        $this->tabs[] = [
            'key' => $key,
            'label' => $label,
            'icon' => $icon,
            'template' => $template,
            'priority' => $priority,
        ];

        usort($this->tabs, fn ($a, $b) => $b['priority'] <=> $a['priority']);
    }

    public function getTabs(): array
    {
        return $this->tabs;
    }

    public function getPlaylist(): Series
    {
        return $this->playlist;
    }
}
