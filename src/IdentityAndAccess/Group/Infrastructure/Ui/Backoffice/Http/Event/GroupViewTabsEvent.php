<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Infrastructure\Ui\Backoffice\Http\Event;

use Pumukit\SchemaBundle\Document\Group;
use Symfony\Contracts\EventDispatcher\Event;

final class GroupViewTabsEvent extends Event
{
    public const NAME = 'group.view_tabs';
    private array $tabs = [];

    public function __construct(
        private readonly Group $group,
        private readonly string $activeTab
    ) {}

    public function addTab(
        string $key,
        string $label,
        string $icon = '',
        int $priority = 100,
        ?string $template = null,
        array $parameters = [],
    ): void {
        $this->tabs[$key] = [
            'key' => $key,
            'label' => $label,
            'icon' => $icon,
            'priority' => $priority,
            'template' => $template ?? "@Group/Views/tabs/{$key}.html.twig",
            'parameters' => $parameters,
        ];
    }

    public function getTabs(): array
    {
        uasort($this->tabs, fn ($a, $b) => $a['priority'] <=> $b['priority']);

        return $this->tabs;
    }

    public function getGroup(): Group
    {
        return $this->group;
    }

    public function getActiveTab(): string
    {
        return $this->activeTab;
    }
}
