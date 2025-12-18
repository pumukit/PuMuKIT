<?php

namespace App\IdentityAndAccess\User\Infrastructure\Persistence;

use App\IdentityAndAccess\User\Domain\Repository\UserRepositoryInterface;
use App\Search\Infrastructure\Persistence\MongoDb\MongoDbCriteriaConverter;
use App\Shared\Domain\Criteria\Criteria;
use App\Shared\Infrastructure\Persistence\DoctrineObjectManager;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use Pumukit\SchemaBundle\Document\User;

class DoctrineUserRepository implements UserRepositoryInterface
{
    private $repository;

    public function __construct(
        private readonly DoctrineObjectManager $objectManager,
        private MongoDbCriteriaConverter $criteriaConverter
    ) {
        $this->repository = $this->objectManager->getDocumentManager()->getRepository(User::class);
    }

    public function findAllUsers(): array
    {
        return $this->repository->findAll();
    }

    public function findByFilters(array $filters = []): array
    {
        $qb = $this->repository->createQueryBuilder();

        $this->applyFilters($qb, $filters);

        $result = $qb->getQuery()->execute();

        return is_array($result) ? $result : (is_iterable($result) ? iterator_to_array($result) : []);
    }

    public function findByFiltersPaginated(array $filters, int $page, int $limit, string $sort, string $order): array
    {
        $qb = $this->repository->createQueryBuilder();

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
        $qb = $this->repository->createQueryBuilder();

        $this->applyFilters($qb, $filters);

        return $qb->count()->getQuery()->execute();
    }

    public function findByIds(array $ids): array
    {
        $searchIds = [];
        foreach ($ids as $id) {
            $searchIds[] = new ObjectId($id);
        }

        return $this->repository->findBy(['_id' => ['$in' => $searchIds]]);
    }

    public function find(string $id): ?User
    {
        return $this->repository->find(new ObjectId($id));
    }

    public function save(User $user): void
    {
        $this->objectManager->getDocumentManager()->persist($user);
        $this->objectManager->getDocumentManager()->flush();
    }

    public function delete(User $user): void
    {
        $this->objectManager->getDocumentManager()->remove($user);
        $this->objectManager->getDocumentManager()->flush();
    }

    public function matching(Criteria $criteria): array
    {
        $qb = $this->repository->createQueryBuilder();

        $this->criteriaConverter->convert($qb, $criteria);

        return $qb->getQuery()->execute()->toArray();
    }

    public function totalMatching(Criteria $criteria): int
    {
        $qb = $this->repository->createQueryBuilder();

        $this->criteriaConverter->convert($qb, new Criteria($criteria->filters()));

        return $qb->count()->getQuery()->execute();
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
