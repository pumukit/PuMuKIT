<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document\MediaType;

interface Media
{
    public function __toString(): string;

    public function originalName(): void;

    public function description($locale = null): ?string;

    public function i18nDescription(): ?array;

    public function isHide(): bool;

    public function tags(): array;

    public function locale(): string;

    public function isDownloadable(): bool;

    public function views(): int;

    public function storage(): Storage;

    public function metadata(): MediaMetadata;
}
