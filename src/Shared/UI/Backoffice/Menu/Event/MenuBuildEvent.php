<?php

declare(strict_types=1);

namespace App\Shared\UI\Backoffice\Menu\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class MenuBuildEvent extends Event
{
    public const NAME = 'shared.menu.build';

    private array $items = [];
    private ?string $currentRoute = null;

    public function __construct(?string $currentRoute = null)
    {
        $this->currentRoute = $currentRoute;
    }

    /**
     * @param string      $key         Unique identifier for the menu item
     * @param string      $label       Label to display
     * @param string      $route       Symfony route name
     * @param string|null $parent      Parent menu key (null for top level)
     * @param string|null $icon        Font Awesome icon class
     * @param int         $priority    Higher priority renders first (default: 0)
     * @param array       $routeParams Route parameters
     * @param string|null $permission  Required permission to show this item
     */
    public function addItem(
        string $key,
        string $label,
        string $route,
        ?string $parent = null,
        ?string $icon = null,
        int $priority = 0,
        array $routeParams = [],
        ?string $permission = null
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
        // Sort by priority
        usort($this->items, fn ($a, $b) => $a['priority'] <=> $b['priority']);

        $hierarchy = [];
        $itemsMap = [];

        foreach ($this->items as $item) {
            $itemsMap[$item['key']] = $item;
        }

        foreach ($this->items as $item) {
            if (null === $item['parent']) {
                $hierarchy[] = &$itemsMap[$item['key']];
            } else {
                if (isset($itemsMap[$item['parent']])) {
                    $itemsMap[$item['parent']]['children'][] = &$itemsMap[$item['key']];
                }
            }
        }

        return $hierarchy;
    }
}
