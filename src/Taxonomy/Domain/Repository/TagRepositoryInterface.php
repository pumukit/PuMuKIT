<?php

declare(strict_types=1);

namespace App\Taxonomy\Domain\Repository;

use Pumukit\SchemaBundle\Document\Tag;

interface TagRepositoryInterface
{
    public function find(string $id): ?Tag;

    public function findByCod(string $cod): ?Tag;

    public function findAll(): array;

    public function findByParent(?Tag $parent): array;

    public function findRoots(): array;

    public function findChildren(Tag $parent): array;

    public function save(Tag $tag): void;

    public function delete(Tag $tag): void;

    public function nextId(): string;
}

