<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Series\Event;

use Pumukit\SchemaBundle\Document\Series;
use Symfony\Contracts\EventDispatcher\Event;

final class SeriesFormBuildEvent extends Event
{
    public const NAME = 'series.form.build';

    private array $formFields = [];

    public function __construct(
        private readonly Series $series
    ) {}

    public function getSeries(): Series
    {
        return $this->series;
    }

    public function addFormField(string $position, string $template, array $data = [], int $priority = 0): void
    {
        $this->formFields[] = [
            'position' => $position,
            'template' => $template,
            'data' => $data,
            'priority' => $priority,
        ];
    }

    public function getFormFields(string $position = null): array
    {
        $fields = $this->formFields;

        if (null !== $position) {
            $fields = array_filter($fields, fn ($field) => $field['position'] === $position);
        }

        usort($fields, fn ($a, $b) => $a['priority'] <=> $b['priority']);

        return $fields;
    }
}
