<?php

declare(strict_types=1);

namespace App\Playlist\Infrastructure\Ui\Backoffice\Http\Event;

use Pumukit\SchemaBundle\Document\Series;

final class PlaylistFormSubmitEvent
{
    public const NAME = 'playlist.form_submit';

    private array $errors = [];

    public function __construct(
        private readonly Series $playlist,
        private readonly array $formData
    ) {}

    public function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
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

    public function getPlaylist(): Series
    {
        return $this->playlist;
    }

    public function getFormData(): array
    {
        return $this->formData;
    }
}
