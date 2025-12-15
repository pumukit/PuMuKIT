<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Infrastructure\Ui\Backoffice\Http\Event;

use Pumukit\SchemaBundle\Document\Series;
use Symfony\Contracts\EventDispatcher\Event;

final class SeriesFormSubmitEvent extends Event
{
    public const NAME = 'series.form.submit';

    private array $errors = [];

    public function __construct(
        private readonly Series $series,
        private array $formData
    ) {}

    public function getSeries(): Series
    {
        return $this->series;
    }

    public function getFormData(): array
    {
        return $this->formData;
    }

    public function getFieldData(string $fieldName): mixed
    {
        return $this->formData[$fieldName] ?? null;
    }

    public function hasField(string $fieldName): bool
    {
        return isset($this->formData[$fieldName]);
    }

    public function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
