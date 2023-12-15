<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Services\DTO;

final class JobOptions
{
    private $profile;
    private $priority;
    private $language;
    private $description;
    private $initVars;
    private $duration;
    private $flags;

    private $unique;

    public function __construct(string $profile, int $priority, $language = 'en', $description = [], $initVars = [], $duration = 0, $flags = 0, bool $unique = false)
    {
        $this->profile = $profile;
        $this->priority = $priority;
        $this->language = $language;
        $this->description = $description;
        $this->initVars = $initVars;
        $this->duration = $duration;
        $this->flags = $flags;
        $this->unique = $unique;
    }

    public function profile(): string
    {
        return $this->profile;
    }
    public function priority(): int
    {
        return $this->priority;
    }
    public function language(): string
    {
        return $this->language;
    }
    public function description(): array
    {
        return $this->description;
    }
    public function initVars(): array
    {
        return $this->initVars;
    }
    public function duration(): int
    {
        return $this->duration;
    }
    public function flags(): int
    {
        return $this->flags;
    }

    public function unique(): bool
    {
        return $this->unique;
    }
}
