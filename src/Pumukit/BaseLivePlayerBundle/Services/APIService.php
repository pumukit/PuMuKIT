<?php

namespace Pumukit\BaseLivePlayerBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Event;
use Pumukit\SchemaBundle\Document\Live;
use Pumukit\SchemaBundle\Repository\EventRepository;
use Pumukit\SchemaBundle\Repository\LiveRepository;

class APIService
{
    private $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    /**
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function getEventsByCriteria(array $criteria, string $sort, int $limit): array
    {
        /** @var EventRepository $qb */
        $qb = $this->documentManager->getRepository(Event::class);

        $qb = $qb->createQueryBuilder();

        if ($criteria) {
            $qb = $qb->addAnd($criteria);
        }

        $qb_event = clone $qb;

        return [
            'total' => $qb->count()->getQuery()->execute(),
            'limit' => $limit,
            'criteria' => $criteria,
            'sort' => $sort,
            'event' => $qb_event->getQuery()->execute()->toArray(),
        ];
    }

    /**
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function getLivesByCriteria(array $criteria, string $sort, int $limit): array
    {
        /** @var LiveRepository $qb */
        $qb = $this->documentManager->getRepository(Live::class);

        $qb = $qb->createQueryBuilder();

        if ($criteria) {
            $qb = $qb->addAnd($criteria);
        }

        $qb_live = clone $qb;

        return [
            'total' => $qb->count()->getQuery()->execute(),
            'limit' => $limit,
            'criteria' => $criteria,
            'sort' => $sort,
            'live' => $qb_live->getQuery()->execute()->toArray(),
        ];
    }
}
