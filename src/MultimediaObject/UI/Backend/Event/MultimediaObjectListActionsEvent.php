<?php

declare(strict_types=1);

namespace App\MultimediaObject\UI\Backend\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class MultimediaObjectListActionsEvent extends Event
{
    public const NAME = 'multimedia_object.list_actions';

    private array $actions = [];

    /**
     * Add a custom action button to the multimedia object list.
     *
     * @param string $key         Unique identifier for the action
     * @param string $label       Button label
     * @param string $url         URL or route name
     * @param string $type        Type: 'url' for direct link, 'route' for Symfony route name
     * @param array  $routeParams Route parameters if type is 'route' (optional)
     * @param string $icon        Font Awesome icon class (optional)
     * @param string $class       CSS classes for the button (default: 'btn btn-primary')
     * @param string $target      Link target: '_self', '_blank', etc. (optional)
     * @param int    $priority    Higher priority renders first (default: 0)
     */
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

