<?php

namespace App\IdentityAndAccess\Group\Infrastructure\Persistence;

use App\IdentityAndAccess\Group\Domain\Repository\GroupRepositoryInterface;
use App\Shared\Infrastructure\Persistence\DoctrineObjectManager;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use Pumukit\SchemaBundle\Document\Group;

class DoctrineGroupRepository implements GroupRepositoryInterface
{
    public function __construct(private readonly DoctrineObjectManager $objectManager) {}

    public function findAllGroups(): array
    {
        return $this->objectManager->getDocumentManager()->getRepository(Group::class)->findAll();
    }

    public function findByFilters(array $filters = []): array
    {
        $qb = $this->objectManager->getDocumentManager()->createQueryBuilder(Group::class);

        $this->applyFilters($qb, $filters);

        $result = $qb->getQuery()->execute();

        return is_array($result) ? $result : (is_iterable($result) ? iterator_to_array($result) : []);
    }

    public function findByFiltersPaginated(array $filters, int $page, int $limit, string $sort, string $order): array
    {
        $qb = $this->objectManager->getDocumentManager()->createQueryBuilder(Group::class);

        $this->applyFilters($qb, $filters);

        $qb->skip(($page - 1) * $limit)
            ->limit($limit)
            ->sort($sort, $order)
        ;

        $result = $qb->getQuery()->execute();

        return is_array($result) ? $result : (is_iterable($result) ? iterator_to_array($result) : []);
    }

    public function countByFilters(array $filters = []): int
    {
        $qb = $this->objectManager->getDocumentManager()->createQueryBuilder(Group::class);

        $this->applyFilters($qb, $filters);

        return $qb->count()->getQuery()->execute();
    }

    public function findByIds(array $ids): array
    {
        $searchIds = [];
        foreach ($ids as $id) {
            $searchIds[] = new ObjectId($id);
        }

        return $this->objectManager->getDocumentManager()->getRepository(Group::class)->findBy(['_id' => ['$in' => $searchIds]]);
    }

    public function find(string $id): ?Group
    {
        return $this->objectManager->getDocumentManager()->getRepository(Group::class)->find(new ObjectId($id));
    }

    public function findByKey(string $key): ?Group
    {
        return $this->objectManager->getDocumentManager()->getRepository(Group::class)->findOneBy(['key' => $key]);
    }

    public function save(Group $group): void
    {
        $this->objectManager->getDocumentManager()->persist($group);
        $this->objectManager->getDocumentManager()->flush();
    }

    public function delete(Group $group): void
    {
        $this->objectManager->getDocumentManager()->remove($group);
        $this->objectManager->getDocumentManager()->flush();
    }

    private function applyFilters($qb, array $filters): void
    {
        if (!empty($filters['key'])) {
            $qb->field('key')->equals($filters['key']);
        }

        if (!empty($filters['name'])) {
            $qb->field('name')->equals(new Regex($filters['name'], 'i'));
        }

        if (!empty($filters['origin'])) {
            $qb->field('origin')->equals($filters['origin']);
        }

        if (!empty($filters['comments'])) {
            $qb->field('comments')->equals(new Regex($filters['comments'], 'i'));
        }
    }
}
