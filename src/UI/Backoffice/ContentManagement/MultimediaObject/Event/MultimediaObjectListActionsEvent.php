<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\MultimediaObject\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class MultimediaObjectListActionsEvent extends Event
{
    public const NAME = 'multimedia_object.list_actions';

    private array $actions = [];

    public function addAction(
        string $key,
        string $label,
        string $url,
        string $type = 'route',
        array $routeParams = [],
        string $icon = '',
        string $class = 'btn btn-primary',
        string $target = '_self',
        int $priority = 0
    ): void {
        $this->actions[] = [
            'key' => $key,
            'label' => $label,
            'url' => $url,
            'type' => $type,
            'route_params' => $routeParams,
            'icon' => $icon,
            'class' => $class,
            'target' => $target,
            'priority' => $priority,
        ];
    }

    public function getActions(): array
    {
        usort($this->actions, fn ($a, $b) => $a['priority'] <=> $b['priority']);

        return $this->actions;
    }

    public function hasActions(): bool
    {
        return !empty($this->actions);
    }
}
