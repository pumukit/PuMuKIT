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
        private readonly Group $group
    ) {
        $this->addTab('general', 'General', 'fa-info-circle', 0);
        $this->addTab('update', 'Update', 'fa-edit', 10);
        $this->addTab('users', 'Users', 'fa-users', 20);
        $this->addTab('multimedia_objects', 'Multimedia Objects', 'fa-photo-video', 30);
    }

    public function addTab(
        string $key,
        string $label,
        string $icon = '',
        int $priority = 100,
        ?string $template = null
    ): void {
        $this->tabs[$key] = [
            'key' => $key,
            'label' => $label,
            'icon' => $icon,
            'priority' => $priority,
            'template' => $template ?? "@Group/Views/tabs/{$key}.html.twig",
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
}

