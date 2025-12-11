<?php

declare(strict_types=1);

namespace App\ContentManagement\Role\Infrastructure\Persistence;

use App\ContentManagement\Role\Domain\Repository\RoleRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\Regex;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Role;

final readonly class DoctrineRoleRepository implements RoleRepositoryInterface
{
    public function __construct(
        private DocumentManager $documentManager
    ) {}

    public function find(string $id): ?Role
    {
        return $this->documentManager->getRepository(Role::class)->find($id);
    }

    public function findByCod(string $cod): ?Role
    {
        return $this->documentManager->getRepository(Role::class)->findOneBy(['cod' => $cod]);
    }

    public function findAll(int $page = 1, int $limit = 10, ?array $sort = null, ?array $filters = []): iterable
    {
        $qb = $this->documentManager
            ->getRepository(Role::class)
            ->createQueryBuilder()
        ;

        if ($sort) {
            $mongoSort = $this->convertSortToMongoFormat($sort);
            $qb->sort($mongoSort);
        }

        $qb->skip(($page - 1) * $limit)
            ->limit($limit)
        ;

        return $qb->getQuery()->execute();
    }

    public function countAll(?array $filters = []): int
    {
        $qb = $this->documentManager
            ->getRepository(Role::class)
            ->createQueryBuilder()
        ;
        return $qb->count()
            ->getQuery()
            ->execute();
    }

    public function save(Role $role): void
    {
        $this->documentManager->persist($role);
        $this->documentManager->flush();
    }

    public function delete(Role $role): void
    {
        $this->documentManager->remove($role);
        $this->documentManager->flush();
    }

    private function convertSortToMongoFormat(array $sort): array
    {
        $mongoSort = [];
        foreach ($sort as $field => $direction) {
            if (is_string($direction)) {
                $mongoSort[$field] = 'asc' === strtolower($direction) ? 1 : -1;
            } elseif (is_int($direction)) {
                $mongoSort[$field] = $direction >= 0 ? 1 : -1;
            } else {
                $mongoSort[$field] = -1;
            }
        }

        return $mongoSort;
    }
}

