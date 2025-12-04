<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence;

use App\Shared\Domain\ObjectManagerInterface;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Doctrine ODM adapter for the ObjectManager interface.
 *
 * This adapter wraps Doctrine MongoDB ODM's DocumentManager to keep the Domain
 * and Application layers independent from the specific persistence framework.
 *
 * This is the ONLY place where Doctrine's DocumentManager should be used directly
 * in new hexagonal architecture code.
 */
final class DoctrineObjectManager implements ObjectManagerInterface
{
    public function __construct(
        private readonly DocumentManager $documentManager
    ) {}

    public function persist(object $object): void
    {
        $this->documentManager->persist($object);
    }

    public function remove(object $object): void
    {
        $this->documentManager->remove($object);
    }

    public function flush(): void
    {
        $this->documentManager->flush();
    }

    public function clear(?string $objectName = null): void
    {
        $this->documentManager->clear($objectName);
    }

    public function refresh(object $object): void
    {
        $this->documentManager->refresh($object);
    }

    public function find(string $className, mixed $id): ?object
    {
        return $this->documentManager->find($className, $id);
    }

    public function getRepository(string $className): object
    {
        return $this->documentManager->getRepository($className);
    }

    /**
     * Gets the underlying Doctrine DocumentManager.
     *
     * WARNING: This method should ONLY be used in Infrastructure layer
     * when you absolutely need access to Doctrine-specific functionality.
     * Avoid using this in Application or Domain layers.
     */
    public function getDocumentManager(): DocumentManager
    {
        return $this->documentManager;
    }
}
