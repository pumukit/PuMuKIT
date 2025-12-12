<?php

declare(strict_types=1);

namespace App\ContentManagement\Person\Infrastructure\Persistence;

use App\ContentManagement\Person\Domain\Repository\PersonRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Person;

final readonly class DoctrinePersonRepository implements PersonRepositoryInterface
{
    public function __construct(
        private DocumentManager $documentManager
    ) {}

    public function find(string $id): ?Person
    {
        return $this->documentManager->getRepository(Person::class)->find($id);
    }

    public function findByEmail(string $email): ?Person
    {
        return $this->documentManager->getRepository(Person::class)->findOneBy(['email' => $email]);
    }

    public function findAll(): array
    {
        return $this->documentManager->getRepository(Person::class)->findAll();
    }

    public function save(Person $person): void
    {
        $this->documentManager->persist($person);
        $this->documentManager->flush();
    }

    public function delete(Person $person): void
    {
        $this->documentManager->remove($person);
        $this->documentManager->flush();
    }
}
