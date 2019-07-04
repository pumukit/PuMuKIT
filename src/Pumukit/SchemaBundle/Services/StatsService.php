<?php

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;

class StatsService
{
    private $dm;
    private $repoMmobj;

    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
        $this->repoMmobj = $this->dm->getRepository(MultimediaObject::class);
    }

    public function getGlobalStats($groupBy = 'month', $sort = -1)
    {
        $dmColl = $this->dm->getDocumentCollection(MultimediaObject::class);
        $dmRepo = $this->dm->getRepository(MultimediaObject::class);

        $mongoProjectDate = $this->getMongoProjectDateArray($groupBy, '$record_date');

        $pipeline = [];
        $criteria = [
            'type' => ['$ne' => MultimediaObject::TYPE_LIVE],
            'status' => ['$ne' => MultimediaObject::STATUS_PROTOTYPE],
        ];
        $pipeline[] = ['$match' => $criteria];

        $this->dm->getFilterCollection()->enable('backoffice');
        $criteria = $this->dm->getFilterCollection()->getFilterCriteria($dmRepo->getClassMetadata());
        if ($criteria) {
            $pipeline[] = ['$match' => $criteria];
        }
        $pipeline[] = [
            '$project' => [
                'date' => $mongoProjectDate,
                'duration' => '$duration',
                'size' => ['$sum' => '$tracks.size'],
            ],
        ];
        $pipeline[] = [
            '$group' => [
                '_id' => '$date',
                'num' => ['$sum' => 1],
                'duration' => ['$sum' => '$duration'],
                'size' => ['$sum' => '$size'],
            ],
        ];
        $pipeline[] = ['$sort' => ['_id' => $sort]];

        $aggregation = $dmColl->aggregate($pipeline, ['cursor' => []]);

        return $aggregation->toArray();
    }

    public function getMmobjRecordedGroupedBy($fromDate = null, $toDate = null, $limit = 100, $page = 0, $criteria = [], $sort = -1, $groupBy = 'month')
    {
        $dmColl = $this->dm->getDocumentCollection(MultimediaObject::class);
        $mongoGroup = ['numMmobjs' => ['$sum' => 1]];

        $aggregation = $this->getAggrRecordedGroupedBy($dmColl, $mongoGroup, 'record_date', $fromDate, $toDate, $limit, $page, $criteria, $sort, $groupBy);

        return $aggregation->toArray();
    }

    public function getSeriesRecordedGroupedBy($fromDate = null, $toDate = null, $limit = 100, $page = 0, $criteria = [], $sort = -1, $groupBy = 'month')
    {
        $dmColl = $this->dm->getDocumentCollection(Series::class);
        $mongoGroup = ['numSeries' => ['$sum' => 1]];

        $aggregation = $this->getAggrRecordedGroupedBy($dmColl, $mongoGroup, 'public_date', $fromDate, $toDate, $limit, $page, $criteria, $sort, $groupBy);

        return $aggregation->toArray();
    }

    public function getHoursRecordedGroupedBy($fromDate = null, $toDate = null, $limit = 100, $page = 0, $criteria = [], $sort = -1, $groupBy = 'month')
    {
        $dmColl = $this->dm->getDocumentCollection(MultimediaObject::class);
        $mongoGroup = ['seconds' => ['$sum' => '$duration']];

        $aggregation = $this->getAggrRecordedGroupedBy($dmColl, $mongoGroup, 'record_date', $fromDate, $toDate, $limit, $page, $criteria, $sort, $groupBy);

        return $aggregation->toArray();
    }

    /**
     * Returns an array for a mongo $project pipeline to create a date-formatted string with just the required fields.
     * It is used for grouping results in date ranges (hour/day/month/year).
     */
    private function getMongoProjectDateArray($groupBy, $dateField = '$date')
    {
        $mongoProjectDate = [];
        switch ($groupBy) {
        case 'hour':
            $mongoProjectDate[] = 'H';
            $mongoProjectDate[] = ['$substr' => [$dateField, 0, 2]];
            $mongoProjectDate[] = 'T';
            // no break
        case 'day':
            $mongoProjectDate[] = ['$substr' => [$dateField, 8, 2]];
            $mongoProjectDate[] = '-';
            // no break
        default: //If it doesn't exists, it's 'month'
        case 'month':
            $mongoProjectDate[] = ['$substr' => [$dateField, 5, 2]];
            $mongoProjectDate[] = '-';
            // no break
        case 'year':
            $mongoProjectDate[] = ['$substr' => [$dateField, 0, 4]];
            break;
        }

        return ['$concat' => array_reverse($mongoProjectDate)];
    }

    /**
     * Returns an aggregation of objects grouped by date.
     */
    private function getAggrRecordedGroupedBy($dmColl, $mongoGroup, $dateName = 'record_date', $fromDate = null, $toDate = null, $limit = 100, $page = 0, $criteria = [], $sort = -1, $groupBy = 'month')
    {
        $matchExtra = [];
        if (!empty($criteria)) {
            $mmobjIds = $this->getIdsWithCriteria($criteria, $this->repoMmobj);
            $matchExtra['_id'] = ['$in' => $mmobjIds];
        }
        if (!$fromDate) {
            $fromDate = new \DateTime();
            $fromDate->setTime(0, 0, 0);
        }
        if (!$toDate) {
            $toDate = new \DateTime();
        }
        $fromMongoDate = new \MongoDate($fromDate->format('U'), $fromDate->format('u'));
        $toMongoDate = new \MongoDate($toDate->format('U'), $toDate->format('u'));

        $pipeline[] = ['$match' => array_merge(
            $matchExtra,
            [$dateName => ['$gte' => $fromMongoDate, '$lte' => $toMongoDate]]),
        ];
        $mongoProjectDate = $this->getMongoProjectDateArray($groupBy, '$'.$dateName);
        $pipeline[] = ['$project' => ['date' => $mongoProjectDate, 'duration' => '$duration']];
        $pipeline[] = ['$group' => array_merge(['_id' => '$date'], $mongoGroup)];
        $pipeline[] = ['$sort' => ['_id' => $sort]];
        $pipeline[] = ['$skip' => $page * $limit];
        $pipeline[] = ['$limit' => $limit];
        $aggregation = $dmColl->aggregate($pipeline, ['cursor' => []]);

        return $aggregation;
    }

    /**
     * Returns an array of MongoIds as results from the criteria.
     */
    private function getIdsWithCriteria($criteria, $repo)
    {
        $mmobjIds = $repo->createQueryBuilder()->addAnd($criteria)->distinct('_id')->getQuery()->execute()->toArray();

        return $mmobjIds;
    }
}
