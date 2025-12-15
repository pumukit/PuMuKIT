<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ui\Backoffice\Menu\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class MenuBuildEvent extends Event
{
    public const NAME = 'shared.menu.build';

    private array $items = [];

    public function __construct(private readonly ?string $currentRoute = null)
    {
    }

    public function addItem(
        string $key,
        string $label,
        string $route,
        ?string $parent = null,
        ?string $icon = null,
        int $priority = 0,
        array $routeParams = [],
        ?string $permission = null,
        array $activeRoutes = []
    ): void {
        $this->items[] = [
            'key' => $key,
            'label' => $label,
            'route' => $route,
            'parent' => $parent,
            'icon' => $icon,
            'priority' => $priority,
            'route_params' => $routeParams,
            'permission' => $permission,
            'active_routes' => $activeRoutes,
            'children' => [],
        ];
    }

    public function addParent(
        string $key,
        string $label,
        ?string $icon = null,
        int $priority = 0,
        ?string $permission = null
    ): void {
        $this->items[] = [
            'key' => $key,
            'label' => $label,
            'route' => null,
            'parent' => null,
            'icon' => $icon,
            'priority' => $priority,
            'route_params' => [],
            'permission' => $permission,
            'children' => [],
        ];
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getCurrentRoute(): ?string
    {
        return $this->currentRoute;
    }

    public function buildHierarchy(): array
    {
        usort($this->items, fn ($a, $b) => $a['priority'] <=> $b['priority']);

        $hierarchy = [];
        $itemsMap = [];

        foreach ($this->items as $item) {
            $itemsMap[$item['key']] = $item;
        }

        foreach ($this->items as $item) {
            if (null === $item['parent']) {
                $hierarchy[] = &$itemsMap[$item['key']];
            } elseif (isset($itemsMap[$item['parent']])) {
                $itemsMap[$item['parent']]['children'][] = &$itemsMap[$item['key']];
            }
        }

        return $hierarchy;
    }
}
