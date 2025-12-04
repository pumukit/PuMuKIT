<?php

declare(strict_types=1);

namespace App\Shared\UI\Backoffice\Header\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class HeaderBuildEvent extends Event
{
    public const NAME = 'shared.header.build';

    private array $items = [];

    /**
     * Add a button/element to the header.
     *
     * @param string      $key          Unique identifier for the header item
     * @param string      $type         Type of element: 'button', 'dropdown', 'link', 'custom'
     * @param string|null $icon         Font Awesome icon class (e.g., 'fa-solid fa-bell')
     * @param string|null $label        Label/text to display (optional for icon-only buttons)
     * @param string|null $route        Symfony route name for links/buttons
     * @param array       $routeParams  Route parameters
     * @param string|null $badge        Badge text (e.g., '99+', '3')
     * @param string|null $badgeClass   CSS class for badge (e.g., 'bg-danger', 'bg-warning')
     * @param string|null $template     Path to custom Twig template for complex elements
     * @param array       $templateVars Variables to pass to the custom template
     * @param int         $priority     Higher priority renders first (default: 0)
     * @param string|null $permission   Required permission to show this item
     * @param string|null $class        Additional CSS classes for the element
     * @param string|null $id           HTML id attribute
     * @param array       $attributes   Additional HTML attributes
     */
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
        // Sort by priority (lower number = higher priority, renders first)
        usort($this->items, fn ($a, $b) => $a['priority'] <=> $b['priority']);

        return $this->items;
    }
}
