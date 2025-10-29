<?php

namespace App\User\Infrastructure\Persistence;

use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use Pumukit\SchemaBundle\Document\User;

class DoctrineUserRepository implements UserRepositoryInterface
{
    public function __construct(private DocumentManager $documentManager) {}

    public function findAllUsers(): array
    {
        return $this->documentManager->getRepository(User::class)->findAll();
    }

    public function findByFilters(array $filters = []): array
    {
        $qb = $this->documentManager->createQueryBuilder(User::class);

        $this->applyFilters($qb, $filters);

        return $qb->getQuery()->execute()->toArray();
    }

    public function findByFiltersPaginated(array $filters, int $page, int $limit, string $sort, string $order): array
    {
        $qb = $this->documentManager->createQueryBuilder(User::class);

        $this->applyFilters($qb, $filters);

        $qb->skip(($page - 1) * $limit)
            ->limit($limit)
            ->sort($sort, $order)
        ;

        return $qb->getQuery()->execute()->toArray();
    }

    public function countByFilters(array $filters = []): int
    {
        $qb = $this->documentManager->createQueryBuilder(User::class);

        $this->applyFilters($qb, $filters);

        return $qb->count()->getQuery()->execute();
    }

    public function findByIds(array $ids): array
    {
        $searchIds = [];
        foreach ($ids as $id) {
            $searchIds[] = new ObjectId($id);
        }

        return $this->documentManager->getRepository(User::class)->findBy(['_id' => ['$in' => $searchIds]]);
    }

    public function find(string $id): ?User
    {
        return $this->documentManager->getRepository(User::class)->find(new ObjectId($id));
    }

    public function save(User $user): void
    {
        $this->documentManager->persist($user);
        $this->documentManager->flush();
    }

    public function delete(User $user): void
    {
        $this->documentManager->remove($user);
        $this->documentManager->flush();
    }

    private function applyFilters($qb, array $filters): void
    {
        if (!empty($filters['username'])) {
            $qb->field('username')->equals($filters['username']);
        }

        if (!empty($filters['email'])) {
            $qb->field('email')->equals($filters['email']);
        }

        if (isset($filters['enabled'])) {
            $qb->field('enabled')->equals((bool) $filters['enabled']);
        }

        if (!empty($filters['fullName'])) {
            $qb->field('fullName')->equals(new Regex($filters['fullName'], 'i'));
        }

        if (!empty($filters['origin'])) {
            $qb->field('origin')->equals($filters['origin']);
        }
    }
}
