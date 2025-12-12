<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Series\Event;

use Pumukit\SchemaBundle\Document\Series;
use Symfony\Contracts\EventDispatcher\Event;

final class SeriesViewTabsEvent extends Event
{
    public const NAME = 'series.view_tabs';

    private array $tabs = [];

    public function __construct(private Series $series) {}

    public function addTab(
        string $key,
        string $label,
        string $icon,
        string $template,
        int $priority = 0
    ): void {
        $this->tabs[] = [
            'key' => $key,
            'label' => $label,
            'icon' => $icon,
            'template' => $template,
            'priority' => $priority,
        ];
    }

    public function getTabs(): array
    {
        usort($this->tabs, fn ($a, $b) => $a['priority'] <=> $b['priority']);

        return $this->tabs;
    }

    public function hasTabs(): bool
    {
        return !empty($this->tabs);
    }

    public function getSeries(): Series
    {
        return $this->series;
    }

    public function getTabByKey(string $key): ?array
    {
        foreach ($this->tabs as $tab) {
            if ($tab['key'] === $key) {
                return $tab;
            }
        }

        return null;
    }
}
