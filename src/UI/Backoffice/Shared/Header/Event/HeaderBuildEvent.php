<?php

declare(strict_types=1);

namespace App\UI\Backoffice\Shared\Header\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class HeaderBuildEvent extends Event
{
    public const NAME = 'shared.header.build';

    private array $items = [];

    public function addItem(
        string $key,
        string $type = 'button',
        ?string $icon = null,
        ?string $label = null,
        ?string $route = null,
        array $routeParams = [],
        ?string $badge = null,
        ?string $badgeClass = 'bg-primary',
        ?string $template = null,
        array $templateVars = [],
        int $priority = 0,
        ?string $permission = null,
        ?string $class = null,
        ?string $id = null,
        array $attributes = []
    ): void {
        $this->items[] = [
            'key' => $key,
            'type' => $type,
            'icon' => $icon,
            'label' => $label,
            'route' => $route,
            'route_params' => $routeParams,
            'badge' => $badge,
            'badge_class' => $badgeClass,
            'template' => $template,
            'template_vars' => $templateVars,
            'priority' => $priority,
            'permission' => $permission,
            'class' => $class,
            'id' => $id,
            'attributes' => $attributes,
        ];
    }

    public function getItems(): array
    {
        usort($this->items, fn ($a, $b) => $a['priority'] <=> $b['priority']);

        return $this->items;
    }
}
