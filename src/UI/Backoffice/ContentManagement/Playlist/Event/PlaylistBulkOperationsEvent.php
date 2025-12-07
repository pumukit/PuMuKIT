<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Playlist\Event;

final class PlaylistBulkOperationsEvent
{
    public const NAME = 'playlist.bulk_operations';

    private array $operations = [];

    public function addOperation(
        string $key,
        string $label,
        string $handler,
        string $type = 'route',
        ?string $icon = null,
        int $priority = 0,
        array $routeParams = []
    ): void {
        $this->operations[$key] = [
            'key' => $key,
            'label' => $label,
            'handler' => $handler,
            'type' => $type,
            'icon' => $icon,
            'priority' => $priority,
            'route_params' => $routeParams,
        ];

        usort($this->operations, fn ($a, $b) => $b['priority'] <=> $a['priority']);
    }

    public function getOperations(): array
    {
        return $this->operations;
    }
}
