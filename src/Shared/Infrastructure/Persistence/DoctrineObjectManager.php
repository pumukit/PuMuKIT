<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence;

use App\Shared\Domain\ObjectManagerInterface;
use Doctrine\ODM\MongoDB\DocumentManager;

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

    public function getDocumentManager(): DocumentManager
    {
        return $this->documentManager;
    }
}
