<?php

declare(strict_types=1);

namespace App\ContentManagement\Taxonomy\Domain;

final class Tag
{
    private ?string $id;
    private string $cod;
    private array $title;
    private array $label;
    private array $description;
    private ?string $slug;
    private bool $metatag;
    private bool $display;
    private int $numberMultimediaObjects;
    private ?Tag $parent;
    private array $children;
    private int $numberChildren;
    private ?string $path;
    private ?int $level;
    private array $properties;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    private function __construct(
        string $cod,
        array $title,
        array $label,
        array $description,
        ?string $slug,
        bool $metatag,
        bool $display,
        ?Tag $parent,
        array $properties
    ) {
        $this->id = null;
        $this->cod = $cod;
        $this->title = $title;
        $this->label = $label;
        $this->description = $description;
        $this->slug = $slug;
        $this->metatag = $metatag;
        $this->display = $display;
        $this->numberMultimediaObjects = 0;
        $this->parent = $parent;
        $this->children = [];
        $this->numberChildren = 0;
        $this->path = null;
        $this->level = null;
        $this->properties = $properties;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public static function create(
        string $cod,
        array $title,
        array $label = [],
        array $description = [],
        ?string $slug = null,
        bool $metatag = false,
        bool $display = false,
        ?Tag $parent = null,
        array $properties = []
    ): self {
        return new self(
            $cod,
            $title,
            $label,
            $description,
            $slug,
            $metatag,
            $display,
            $parent,
            $properties
        );
    }

    public function update(
        array $title,
        array $label,
        array $description,
        ?string $slug,
        bool $metatag,
        bool $display,
        array $properties
    ): void {
        $this->title = $title;
        $this->label = $label;
        $this->description = $description;
        $this->slug = $slug;
        $this->metatag = $metatag;
        $this->display = $display;
        $this->properties = $properties;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function setParent(?Tag $parent): void
    {
        $this->parent = $parent;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function addChild(Tag $child): void
    {
        $this->children[] = $child;
        ++$this->numberChildren;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function increaseNumberMultimediaObjects(): void
    {
        ++$this->numberMultimediaObjects;
    }

    public function decreaseNumberMultimediaObjects(): void
    {
        --$this->numberMultimediaObjects;
    }

    public function id(): ?string
    {
        return $this->id;
    }

    public function cod(): string
    {
        return $this->cod;
    }

    public function title(string $locale = 'en'): string
    {
        return $this->title[$locale] ?? '';
    }

    public function allTitles(): array
    {
        return $this->title;
    }

    public function label(string $locale = 'en'): string
    {
        if (!isset($this->label[$locale]) || '' === $this->label[$locale]) {
            return $this->title($locale);
        }

        return $this->label[$locale];
    }

    public function allLabels(): array
    {
        return $this->label;
    }

    public function description(string $locale = 'en'): string
    {
        return $this->description[$locale] ?? '';
    }

    public function allDescriptions(): array
    {
        return $this->description;
    }

    public function slug(): ?string
    {
        return $this->slug;
    }

    public function isMetatag(): bool
    {
        return $this->metatag;
    }

    public function isDisplay(): bool
    {
        return $this->display;
    }

    public function numberMultimediaObjects(): int
    {
        return $this->numberMultimediaObjects;
    }

    public function parent(): ?Tag
    {
        return $this->parent;
    }

    public function children(): array
    {
        return $this->children;
    }

    public function numberChildren(): int
    {
        return $this->numberChildren;
    }

    public function path(): ?string
    {
        return $this->path;
    }

    public function level(): ?int
    {
        return $this->level;
    }

    public function properties(): array
    {
        return $this->properties;
    }

    public function property(string $key)
    {
        return $this->properties[$key] ?? null;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function setPath(?string $path): void
    {
        $this->path = $path;
    }

    public function setLevel(?int $level): void
    {
        $this->level = $level;
    }

    public function setNumberMultimediaObjects(int $count): void
    {
        $this->numberMultimediaObjects = $count;
    }

    public function setNumberChildren(int $count): void
    {
        $this->numberChildren = $count;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
