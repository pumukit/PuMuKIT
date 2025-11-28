<?php

declare(strict_types=1);

namespace App\Series\UI\Backoffice\Event;

use Pumukit\SchemaBundle\Document\Series;
use Symfony\Contracts\EventDispatcher\Event;

final class SeriesViewTabsEvent extends Event
{
    public const NAME = 'series.view_tabs';

    private array $tabs = [];

    public function __construct(private Series $series) {}

    /**
     * Add a custom tab to the series view page.
     *
     * @param string $key      Unique identifier for the tab
     * @param string $label    Tab label
     * @param string $icon     Font Awesome icon class (e.g., 'fa-solid fa-chart-bar')
     * @param string $template Template path to render when tab is active
     * @param int    $priority Higher priority renders first (default: 0)
     */
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
