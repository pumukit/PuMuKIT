<?php

namespace App\User\Infrastructure\Persistence;

use App\User\Domain\UserRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\User;

class DoctrineUserRepository implements UserRepositoryInterface
{
    public function __construct(private DocumentManager $documentManager)
    {
    }

    public function findAllUsers(): array
    {
        return $this->documentManager->getRepository(User::class)->findAll();
    }

    public function findByFilters(array $filters = []): array
    {
        $qb = $this->documentManager->createQueryBuilder(User::class);

        if (!empty($filters['username'])) {
            $qb->field('username')->equals($filters['username']);
        }

        if (!empty($filters['email'])) {
            $qb->field('email')->equals($filters['email']);
        }

        return $qb->getQuery()->execute()->toArray();
    }

    public function findByIds(array $ids): array
    {
        $searchIds = [];
        foreach ($ids as $id) {
            $searchIds[] = new \MongoDB\BSON\ObjectId($id);
        }

        return $this->documentManager->getRepository(User::class)->findBy(['_id' => ['$in' => $searchIds]]);
    }
}
