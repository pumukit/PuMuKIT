<?php

namespace App\Playlist\Infrastructure\Persistence;

use App\Playlist\Domain\Repository\PlaylistRepositoryInterface;
use App\Shared\Infrastructure\Persistence\DoctrineObjectManager;
use Pumukit\SchemaBundle\Document\Series;

final class DoctrinePlaylistRepository implements PlaylistRepositoryInterface
{
    public function __construct(
        private DoctrineObjectManager $objectManager
    ) {}

    public function findAll(): iterable
    {
        return $this->objectManager->getDocumentManager()
            ->getRepository(Series::class)
            ->findBy(['type' => Series::TYPE_PLAYLIST])
        ;
    }

    public function find(string $id): ?object
    {
        $playlist = $this->objectManager->getDocumentManager()
            ->getRepository(Series::class)
            ->find($id)
        ;

        if ($playlist && $playlist->isPlaylist()) {
            return $playlist;
        }

        return null;
    }

    public function findByFilters(array $filters = []): iterable
    {
        $filters['type'] = Series::TYPE_PLAYLIST;

        return $this->objectManager->getDocumentManager()
            ->getRepository(Series::class)
            ->findBy($filters)
        ;
    }

    public function findByFiltersPaginated(array $filters, int $page, int $limit, ?string $sort = null, ?string $order = null): array
    {
        $filters['type'] = Series::TYPE_PLAYLIST;

        $qb = $this->objectManager->getDocumentManager()
            ->createQueryBuilder(Series::class)
            ->field('type')->equals(Series::TYPE_PLAYLIST)
        ;

        foreach ($filters as $field => $value) {
            if ('type' === $field) {
                continue;
            }
            if (is_array($value)) {
                $orX = [];
                foreach ($value as $v) {
                    $orX[] = $qb->expr()->field($field)->equals(new \MongoRegex('/.*'.$v.'.*/i'));
                }
                $qb->addOr($orX);
            } else {
                $qb->field($field)->equals(new \MongoRegex('/.*'.$value.'.*/i'));
            }
        }

        if ($sort) {
            $qb->sort($sort, 'asc' === strtolower($order) ? 1 : -1);
        }

        $qb->skip(($page - 1) * $limit)
            ->limit($limit)
        ;

        return $qb->getQuery()->execute()->toArray();
    }

    public function countByFilters(array $filters): int
    {
        $filters['type'] = Series::TYPE_PLAYLIST;

        $qb = $this->objectManager->getDocumentManager()
            ->createQueryBuilder(Series::class)
            ->field('type')->equals(Series::TYPE_PLAYLIST)
            ->count()
        ;

        foreach ($filters as $field => $value) {
            if ('type' === $field) {
                continue;
            }
            if (is_array($value)) {
                $orX = [];
                foreach ($value as $v) {
                    $orX[] = $qb->expr()->field($field)->equals(new \MongoRegex('/.*'.$v.'.*/i'));
                }
                $qb->addOr($orX);
            } else {
                $qb->field($field)->equals(new \MongoRegex('/.*'.$value.'.*/i'));
            }
        }

        return $qb->getQuery()->execute();
    }

    public function save(Series $playlist): void
    {
        $this->objectManager->getDocumentManager()->persist($playlist);
        $this->objectManager->getDocumentManager()->flush();
    }

    public function delete(Series $playlist): void
    {
        $this->objectManager->getDocumentManager()->remove($playlist);
        $this->objectManager->getDocumentManager()->flush();
    }

    public function countMultimediaObjects(string $playlistId): int
    {
        $playlist = $this->find($playlistId);

        if (!$playlist) {
            return 0;
        }

        return $playlist->getPlaylist()->getMultimediaObjects()->count();
    }
}
