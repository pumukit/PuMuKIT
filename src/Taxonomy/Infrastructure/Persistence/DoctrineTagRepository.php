<?php

declare(strict_types=1);

namespace App\Taxonomy\Infrastructure\Persistence;

use App\Taxonomy\Domain\Repository\TagRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Pumukit\SchemaBundle\Document\Tag as LegacyTag;

final class DoctrineTagRepository implements TagRepositoryInterface
{
    private DocumentRepository $repository;

    public function __construct(
        private readonly DocumentManager $documentManager
    ) {
        $this->repository = $documentManager->getRepository(LegacyTag::class);
    }

    public function find(string $id): ?LegacyTag
    {
        return $this->repository->find($id);
    }

    public function findByCod(string $cod): ?LegacyTag
    {
        return $this->repository->findOneBy(['cod' => $cod]);
    }

    public function findAll(): array
    {
        return $this->repository->findAll();
    }

    public function findByParent(?LegacyTag $parent): array
    {
        $parentId = $parent ? $parent->getId() : null;

        return $this->repository->findBy(['parent' => $parentId]);
    }

    public function findRoots(): array
    {
        return $this->repository->findBy(['parent' => null]);
    }

    public function findChildren(LegacyTag $parent): array
    {
        $legacyParent = $this->repository->find($parent->getId());
        if (!$legacyParent) {
            return [];
        }

        $legacyChildren = $legacyParent->getChildren();

        return iterator_to_array($legacyChildren);
    }

    public function save(LegacyTag $tag): void
    {
        $this->documentManager->persist($tag);
        $this->documentManager->flush();
    }

    public function delete(LegacyTag $tag): void
    {
        $this->documentManager->remove($tag);
        $this->documentManager->flush();
    }

    public function nextId(): string
    {
        return uniqid('tag_', true);
    }
}
