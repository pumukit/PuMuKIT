<?php

declare(strict_types=1);

namespace App\UI\Backoffice\IdentityAndAccess\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class UserBulkOperationsEvent extends Event
{
    public const NAME = 'user.bulk_operations';

    private array $operations = [];

    /**
     * @param string $key            Unique identifier for the operation
     * @param string $label          Label to show in the dropdown
     * @param string $handler        JavaScript handler function name, URL endpoint, or Symfony route name
     * @param string $type           Type of handler: 'js' for JavaScript function, 'url' for AJAX endpoint, 'route' for Symfony route
     * @param string $icon           Font Awesome icon class (optional)
     * @param string $confirmMessage Confirmation message before executing (optional)
     * @param array  $routeParams    Route parameters if type is 'route' (optional)
     * @param int    $priority       Higher priority renders first (default: 0)
     */
    public function addOperation(
        string $key,
        string $label,
        string $handler,
        string $type = 'route',
        string $icon = '',
        string $confirmMessage = '',
        array $routeParams = [],
        int $priority = 0
    ): void {
        $this->operations[] = [
            'key' => $key,
            'label' => $label,
            'handler' => $handler,
            'type' => $type,
            'icon' => $icon,
            'confirm_message' => $confirmMessage,
            'route_params' => $routeParams,
            'priority' => $priority,
        ];
    }

    public function getOperations(): array
    {
        usort($this->operations, fn ($a, $b) => $a['priority'] <=> $b['priority']);

        return $this->operations;
    }

    public function hasOperations(): bool
    {
        return !empty($this->operations);
    }
}
