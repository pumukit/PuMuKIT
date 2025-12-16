<?php

namespace App\IdentityAndAccess\Authorization\Infrastructure\Persistence;

use App\IdentityAndAccess\Authorization\Domain\Repository\PermissionProfileRepositoryInterface;
use App\Shared\Infrastructure\Persistence\DoctrineObjectManager;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use Pumukit\SchemaBundle\Document\PermissionProfile;

class DoctrinePermissionProfileRepository implements PermissionProfileRepositoryInterface
{
    public function __construct(private readonly DoctrineObjectManager $objectManager) {}

    public function findAll(): array
    {
        return $this->objectManager->getDocumentManager()->getRepository(PermissionProfile::class)->findAll();
    }

    public function findByFilters(array $filters = []): array
    {
        $qb = $this->objectManager->getDocumentManager()->createQueryBuilder(PermissionProfile::class);

        $this->applyFilters($qb, $filters);

        $result = $qb->getQuery()->execute();

        return is_array($result) ? $result : (is_iterable($result) ? iterator_to_array($result) : []);
    }

    public function findByFiltersPaginated(array $filters, int $page, int $limit, string $sort, string $order): array
    {
        $qb = $this->objectManager->getDocumentManager()->createQueryBuilder(PermissionProfile::class);

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
        $qb = $this->objectManager->getDocumentManager()->createQueryBuilder(PermissionProfile::class);

        $this->applyFilters($qb, $filters);

        return $qb->count()->getQuery()->execute();
    }

    public function findByIds(array $ids): array
    {
        $searchIds = [];
        foreach ($ids as $id) {
            $searchIds[] = new ObjectId($id);
        }

        return $this->objectManager->getDocumentManager()->getRepository(PermissionProfile::class)->findBy(['_id' => ['$in' => $searchIds]]);
    }

    public function find(string $id): ?PermissionProfile
    {
        return $this->objectManager->getDocumentManager()->getRepository(PermissionProfile::class)->find(new ObjectId($id));
    }

    public function save(PermissionProfile $permissionProfile): void
    {
        $this->objectManager->getDocumentManager()->persist($permissionProfile);
        $this->objectManager->getDocumentManager()->flush();
    }

    public function delete(PermissionProfile $permissionProfile): void
    {
        $this->objectManager->getDocumentManager()->remove($permissionProfile);
        $this->objectManager->getDocumentManager()->flush();
    }

    public function findDefault(): ?PermissionProfile
    {
        return $this->objectManager->getDocumentManager()->getRepository(PermissionProfile::class)->findOneBy(['default' => true]);
    }

    public function findByName(string $name): ?PermissionProfile
    {
        return $this->objectManager->getDocumentManager()->getRepository(PermissionProfile::class)->findOneBy(['name' => $name]);
    }

    private function applyFilters($qb, array $filters): void
    {
        if (!empty($filters['name'])) {
            $qb->field('name')->equals(new Regex($filters['name'], 'i'));
        }

        if (isset($filters['system'])) {
            $qb->field('system')->equals((bool) $filters['system']);
        }

        if (isset($filters['default'])) {
            $qb->field('default')->equals((bool) $filters['default']);
        }

        if (!empty($filters['scope'])) {
            $qb->field('scope')->equals($filters['scope']);
        }

        if (!empty($filters['permission'])) {
            $qb->field('permissions')->equals($filters['permission']);
        }
    }
}
