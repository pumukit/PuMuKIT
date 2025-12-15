<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ui\Backoffice\Http\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class DashboardBuildEvent extends Event
{
    public const NAME = 'shared.dashboard.build';

    private array $widgets = [];

    public function addWidget(
        string $key,
        string $template,
        array $vars = [],
        string $type = 'card',
        string $columnSize = 'col-md-3',
        int $priority = 0
    ): void {
        $this->widgets[] = [
            'key' => $key,
            'type' => $type,
            'template' => $template,
            'template_vars' => $vars,
            'column_size' => $columnSize,
            'priority' => $priority,
        ];
    }

    public function getWidgets(): array
    {
        usort($this->widgets, fn ($a, $b) => $a['priority'] <=> $b['priority']);

        return $this->widgets;
    }
}
