<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Document;

interface ElementInterface
{
    public function getId();

    public function setTags(array $tags): void;

    public function getTags(): array;

    public function addTag(string $tag): array;

    public function removeTag($tag): bool;

    public function containsTag($tag): bool;

    public function containsAllTags(array $tags): bool;

    public function containsAnyTag(array $tags): bool;

    public function setUrl(string $url): void;

    public function getUrl(): ?string;

    public function setPath(string $path): void;

    public function getPath(): ?string;

    public function setMimeType(string $mime_type): void;

    public function getMimeType(): ?string;

    public function setSize(int $size): void;

    public function getSize(): ?int;

    public function setHide(bool $hide): void;

    public function getHide(): bool;

    public function isHide(): bool;

    public function setDescription(?string $description, string $locale = null): void;

    public function getDescription($locale = null): ?string;

    public function setI18nDescription(array $description): void;

    public function getI18nDescription(): ?array;

    public function setLocale(string $locale): void;

    public function getLocale(): string;
}
