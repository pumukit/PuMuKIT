<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\User\Infrastructure\Ui\Backoffice\Http\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class UserBulkOperationsEvent extends Event
{
    public const NAME = 'user.bulk_operations';

    private array $operations = [];

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
