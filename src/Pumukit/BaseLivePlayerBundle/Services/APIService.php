<?php

namespace Pumukit\BaseLivePlayerBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Event;
use Pumukit\SchemaBundle\Document\Live;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class APIService
{
    private $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    public function getEventsByCriteria(array $criteria, string $sort, int $limit): array
    {
        $qb = $this->documentManager->getRepository(MultimediaObject::class)->createQueryBuilder();
        $qb->addAnd($qb->expr()->field('type')->equals(MultimediaObject::TYPE_LIVE));

        if ($criteria) {
            $qb = $qb->addAnd($criteria);
        }

        $qb_event = clone $qb;

        return [
            'total' => $qb->count()->getQuery()->execute(),
            'limit' => $limit,
            'criteria' => $criteria,
            'sort' => $sort,
            'event' => $qb_event->getQuery()->execute(),
        ];
    }

    public function getLivesByCriteria(array $criteria, string $sort, int $limit): array
    {
        $qb = $this->documentManager->getRepository(Live::class)->createQueryBuilder();

        if ($criteria) {
            $qb = $qb->addAnd($criteria);
        }

        $qb_live = clone $qb;

        return [
            'total' => $qb->count()->getQuery()->execute(),
            'limit' => $limit,
            'criteria' => $criteria,
            'sort' => $sort,
            'live' => $qb_live->getQuery()->execute(),
        ];
    }
}
