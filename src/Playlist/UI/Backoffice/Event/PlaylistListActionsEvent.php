<?php

declare(strict_types=1);

namespace App\Playlist\UI\Backoffice\Event;

final class PlaylistListActionsEvent
{
    public const NAME = 'playlist.list_actions';

    private array $actions = [];

    public function addAction(
        string $key,
        string $label,
        string $url,
        string $type = 'route',
        ?string $icon = null,
        ?string $class = null,
        int $priority = 0,
        array $routeParams = []
    ): void {
        $this->actions[$key] = [
            'key' => $key,
            'label' => $label,
            'url' => $url,
            'type' => $type,
            'icon' => $icon,
            'class' => $class,
            'priority' => $priority,
            'route_params' => $routeParams,
        ];

        usort($this->actions, fn ($a, $b) => $b['priority'] <=> $a['priority']);
    }

    public function getActions(): array
    {
        return $this->actions;
    }
}
