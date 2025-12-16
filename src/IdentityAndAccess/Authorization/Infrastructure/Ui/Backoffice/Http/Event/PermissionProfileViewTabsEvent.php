<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Authorization\Infrastructure\Ui\Backoffice\Http\Event;

use Pumukit\SchemaBundle\Document\PermissionProfile;
use Symfony\Contracts\EventDispatcher\Event;

final class PermissionProfileViewTabsEvent extends Event
{
    public const NAME = 'permission_profile.view_tabs';

    private array $tabs = [];

    public function __construct(
        private readonly PermissionProfile $permissionProfile
    ) {
        // Add default tabs
        $this->addTab('general', 'General', 'fa-info-circle', 0);
        $this->addTab('permissions', 'Permissions', 'fa-key', 10);
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
            'template' => $template ?? "@PermissionProfile/Views/tabs/{$key}.html.twig",
        ];
    }

    public function getTabs(): array
    {
        uasort($this->tabs, fn ($a, $b) => $a['priority'] <=> $b['priority']);

        return $this->tabs;
    }

    public function getPermissionProfile(): PermissionProfile
    {
        return $this->permissionProfile;
    }
}
