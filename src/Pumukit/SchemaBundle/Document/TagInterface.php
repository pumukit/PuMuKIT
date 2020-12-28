<?php
declare(strict_types=1);
namespace Pumukit\SchemaBundle\Document;

interface TagInterface
{
    public function __toString(): string;

    public function getId();

    public function getTitle(string $locale = null): string;

    public function setTitle(string $title, string $locale = null): void;

    public function getI18nTitle(): ?array;

    public function setI18nTitle(array $title): void;

    public function getDescription(string $locale = null): ?string;

    public function setDescription(string $description, string $locale = null): void;

    public function setI18nDescription(array $description): void;

    public function getI18nDescription(): ?array;

    public function getCod(): string;

    public function setCod(string $code): void;

    public function getMetaTag(): ?bool;

    public function setMetaTag(bool $metaTag): void;

    public function setDisplay(bool $display): void;

    public function isDisplay(): bool;

    public function getPath(): ?string;

    public function getLevel(): ?int;
}
