<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
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

    public function getGlobalStats($groupBy = 'month', $sort = -1, ?string $ownerId = null)
    {
        $dmColl = $this->dm->getDocumentCollection(MultimediaObject::class);
        $dmRepo = $this->dm->getRepository(MultimediaObject::class);

        $mongoProjectDate = $this->getMongoProjectDateArray($groupBy, '$record_date');

        $pipeline = [];
        $criteria = [
            'type' => ['$ne' => MultimediaObject::TYPE_LIVE],
            'status' => ['$ne' => MultimediaObject::STATUS_PROTOTYPE],
        ];
        if ($ownerId) {
            $criteria['properties.owners'] = ['$in' => [$ownerId]];
        }
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
     * Aggregate multimedia objects by their creation timestamp into (date, hour-of-day, count) buckets.
     *
     * Reads `properties.created`, which the MultimediaObject constructor stores via
     * Properties::setPropertyAsDateTime as an ISO 8601 string ("YYYY-MM-DDTHH:MM:SS±TZ"), NOT a BSON
     * Date. We slice the string directly so the result preserves the wall-clock hour as it was
     * recorded, instead of getting shifted by $hour's UTC normalisation.
     */
    public function getMmobjActivityByDayHour(string $groupBy = 'day', ?string $ownerId = null): array
    {
        $dateExpr = match ($groupBy) {
            'year' => ['$concat' => [['$substrCP' => ['$properties.created', 0, 4]], '-01-01']],
            'month' => ['$concat' => [['$substrCP' => ['$properties.created', 0, 7]], '-01']],
            default => ['$substrCP' => ['$properties.created', 0, 10]],
        };
        // 4-hour buckets: floor(hour / 4) * 4 → 0, 4, 8, 12, 16, 20.
        $hourExpr = ['$multiply' => [
            ['$toInt' => ['$divide' => [
                ['$toInt' => ['$substrCP' => ['$properties.created', 11, 2]]],
                4,
            ]]],
            4,
        ]];

        $fromIso = $this->getActivityWindowStart($groupBy)->format('c');

        $dmColl = $this->dm->getDocumentCollection(MultimediaObject::class);
        $dmRepo = $this->dm->getRepository(MultimediaObject::class);

        $match = [
            'type' => ['$ne' => MultimediaObject::TYPE_LIVE],
            'status' => ['$ne' => MultimediaObject::STATUS_PROTOTYPE],
            'properties.created' => ['$type' => 'string', '$gte' => $fromIso],
        ];
        if ($ownerId) {
            $match['properties.owners'] = ['$in' => [$ownerId]];
        }
        $pipeline = [['$match' => $match]];

        $this->dm->getFilterCollection()->enable('backoffice');
        $filterCriteria = $this->dm->getFilterCollection()->getFilterCriteria($dmRepo->getClassMetadata());
        if ($filterCriteria) {
            $pipeline[] = ['$match' => $filterCriteria];
        }

        $pipeline[] = ['$project' => ['date' => $dateExpr, 'hour' => $hourExpr]];
        $pipeline[] = ['$group' => [
            '_id' => ['date' => '$date', 'hour' => '$hour'],
            'count' => ['$sum' => 1],
        ]];
        $pipeline[] = ['$sort' => ['_id.date' => 1, '_id.hour' => 1]];

        $rows = $dmColl->aggregate($pipeline, ['cursor' => []])->toArray();

        return array_map(static fn ($r) => [
            'date' => $r['_id']['date'],
            'hour' => $r['_id']['hour'],
            'count' => $r['count'],
        ], $rows);
    }

    public function getActivityWindowStart(string $groupBy): \DateTimeImmutable
    {
        $now = new \DateTimeImmutable('now');

        return match ($groupBy) {
            'year' => $now->modify('-9 years')->modify('first day of January')->setTime(0, 0),
            'month' => $now->modify('-11 months')->modify('first day of this month')->setTime(0, 0),
            default => $now->modify('-29 days')->setTime(0, 0),
        };
    }

    /**
     * Returns an array for a mongo $project pipeline to create a date-formatted string with just the required fields.
     * It is used for grouping results in date ranges (hour/day/month/year).
     *
     * @param mixed $groupBy
     * @param mixed $dateField
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
            default: // If it doesn't exists, it's 'month'
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

    private function getAggrRecordedGroupedBy(Collection $dmColl, $mongoGroup, $dateName = 'record_date', $fromDate = null, $toDate = null, $limit = 100, $page = 0, $criteria = [], $sort = -1, $groupBy = 'month')
    {
        $pipeline = [];
        $matchExtra = [];
        if (!empty($criteria)) {
            $mmobjIds = $this->getIdsWithCriteria($criteria, $this->repoMmobj);
            $matchExtra['_id'] = ['$in' => $mmobjIds];
        } else {
            $pipeline[] = [
                '$match' => [
                    'status' => [
                        '$nin' => [MultimediaObject::STATUS_PROTOTYPE, MultimediaObject::STATUS_NEW],
                    ],
                    'type' => ['$ne' => MultimediaObject::TYPE_LIVE],
                ],
            ];
        }

        if (!$fromDate) {
            $fromDate = new \DateTime();
            $fromDate->setTime(0, 0, 0);
        }
        if (!$toDate) {
            $toDate = new \DateTime();
        }

        $fromMongoDate = new UTCDateTime($fromDate);
        $toMongoDate = new UTCDateTime($toDate);

        $pipeline[] = ['$match' => array_merge(
            $matchExtra,
            [$dateName => ['$gte' => $fromMongoDate, '$lte' => $toMongoDate]]
        ),
        ];
        $mongoProjectDate = $this->getMongoProjectDateArray($groupBy, '$'.$dateName);
        $pipeline[] = ['$project' => ['date' => $mongoProjectDate, 'duration' => '$duration']];
        $pipeline[] = ['$group' => array_merge(['_id' => '$date'], $mongoGroup)];
        $pipeline[] = ['$sort' => ['_id' => $sort]];
        $pipeline[] = ['$skip' => $page * $limit];
        $pipeline[] = ['$limit' => $limit];

        return $dmColl->aggregate($pipeline, ['cursor' => []]);
    }

    /**
     * Returns an array of MongoIds as results from the criteria.
     *
     * @param mixed $criteria
     * @param mixed $repo
     */
    private function getIdsWithCriteria($criteria, $repo)
    {
        return $repo->createQueryBuilder()
            ->field('status')->notIn([MultimediaObject::STATUS_PROTOTYPE, MultimediaObject::STATUS_NEW])
            ->field('type')->notEqual(MultimediaObject::TYPE_LIVE)
            ->addAnd($criteria)->distinct('_id')->getQuery()->execute()->toArray()
        ;
    }
}
