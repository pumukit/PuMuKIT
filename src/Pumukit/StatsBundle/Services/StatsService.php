<?php

namespace Pumukit\StatsBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;

class StatsService
{
    private $dm;
    private $repo;
    private $repoSeries;

    private $collectionName;
    private $sumValue;

    public function __construct(DocumentManager $documentManager, $useAggregation = false)
    {
        $this->dm = $documentManager;
        $this->repo = $this->dm->getRepository(MultimediaObject::class);
        $this->repoSeries = $this->dm->getRepository(Series::class);
        $this->collectionName = $useAggregation ? 'PumukitStatsBundle:ViewsAggregation' : 'PumukitStatsBundle:ViewsLog';
        $this->sumValue = $useAggregation ? '$numView' : 1;
    }

    public function doGetMostViewed(array $criteria = [], $days = 30, $limit = 3)
    {
        $ids = [];
        $fromDate = new \DateTime(sprintf('-%s days', $days));
        $fromMongoDate = new \MongoDate($fromDate->format('U'), $fromDate->format('u'));
        $viewsLogColl = $this->dm->getDocumentCollection($this->collectionName);

        $pipeline = [
            ['$match' => ['date' => ['$gte' => $fromMongoDate]]],
            ['$group' => ['_id' => '$multimediaObject', 'numView' => ['$sum' => $this->sumValue]]],
            ['$sort' => ['numView' => -1]],
            ['$limit' => $limit * 2], //Get more elements due to tags post-filter.
        ];

        $aggregation = $viewsLogColl->aggregate($pipeline, ['cursor' => []]);

        $mostViewed = [];

        foreach ($aggregation as $element) {
            $ids[] = $element['_id'];
            $criteria['_id'] = $element['_id'];
            $multimediaObject = $this->repo->findBy($criteria, null, 1);

            if ($multimediaObject) {
                $mostViewed[] = $multimediaObject[0];
                if (0 == --$limit) {
                    break;
                }
            }
        }

        if (0 !== $limit) {
            $criteria['_id'] = ['$nin' => $ids];

            return array_merge($mostViewed, $this->repo->findStandardBy($criteria, null, $limit));
        }

        return $mostViewed;
    }

    public function getMostViewed(array $tags, $days = 30, $limit = 3)
    {
        $criteria = [];
        if ($tags) {
            $criteria['tags.cod'] = ['$all' => $tags];
        }

        return $this->doGetMostViewed($criteria, $days, $limit);
    }

    public function getMostViewedUsingFilters($days = 30, $limit = 3)
    {
        $filters = $this->dm->getFilterCollection()->getFilterCriteria($this->repo->getClassMetadata());

        return $this->doGetMostViewed($filters, $days, $limit);
    }

    /**
     * Returns an array of mmobj viewed on the given range and its number of views on that range.
     */
    public function getMmobjsMostViewedByRange(array $criteria = [], array $options = [])
    {
        $ids = [];

        $viewsLogColl = $this->dm->getDocumentCollection($this->collectionName);

        $matchExtra = [];
        $mmobjIds = $this->getMmobjIdsWithCriteria($criteria);
        $matchExtra['multimediaObject'] = ['$in' => $mmobjIds];

        $options = $this->parseOptions($options);

        $pipeline = [];
        $pipeline = $this->aggrPipeAddMatch($options['from_date'], $options['to_date'], $matchExtra);
        $pipeline[] = ['$group' => ['_id' => '$multimediaObject', 'numView' => ['$sum' => $this->sumValue]]];
        $pipeline[] = ['$sort' => ['numView' => $options['sort']]];

        $aggregation = $viewsLogColl->aggregate($pipeline, ['cursor' => []]);

        $totalInAggegation = count($aggregation);
        $total = count($mmobjIds);
        $aggregation = $this->getPagedAggregation($aggregation->toArray(), $options['page'], $options['limit']);

        $mostViewed = [];
        foreach ($aggregation as $element) {
            $ids[] = $element['_id'];
            $multimediaObject = $this->repo->find($element['_id']);
            if ($multimediaObject) {
                $mostViewed[] = ['mmobj' => $multimediaObject,
                    'num_viewed' => $element['numView'],
                ];
            }
        }

        //Add mmobj with zero views
        if (count($aggregation) < $options['limit']) {
            if (0 == count($aggregation)) {
                $max = min((1 + $options['page']) * $options['limit'], $total);
                for ($i = ($options['page'] * $options['limit']); $i < $max; ++$i) {
                    $multimediaObject = $this->repo->find($mmobjIds[$i - $totalInAggegation]);
                    if ($multimediaObject) {
                        $mostViewed[] = ['mmobj' => $multimediaObject,
                            'num_viewed' => 0,
                        ];
                    }
                }
            } else {
                foreach ($mmobjIds as $element) {
                    if (!in_array($element, $ids)) {
                        $multimediaObject = $this->repo->find($element);
                        if ($multimediaObject) {
                            $mostViewed[] = ['mmobj' => $multimediaObject,
                                'num_viewed' => 0,
                            ];
                            if (count($mostViewed) == $options['limit']) {
                                break;
                            }
                        }
                    }
                }
            }
        }

        return [$mostViewed, $total];
    }

    /**
     * Returns an array of series viewed on the given range and its number of views on that range.
     */
    public function getSeriesMostViewedByRange(array $criteria = [], array $options = [])
    {
        $ids = [];
        $viewsLogColl = $this->dm->getDocumentCollection($this->collectionName);

        $matchExtra = [];

        $seriesIds = $this->getSeriesIdsWithCriteria($criteria);
        $matchExtra['series'] = ['$in' => $seriesIds];

        $options = $this->parseOptions($options);

        $pipeline = [];
        $pipeline = $this->aggrPipeAddMatch($options['from_date'], $options['to_date'], $matchExtra);
        $pipeline[] = ['$group' => ['_id' => '$series', 'numView' => ['$sum' => $this->sumValue]]];
        $pipeline[] = ['$sort' => ['numView' => $options['sort']]];

        $aggregation = $viewsLogColl->aggregate($pipeline, ['cursor' => []]);

        $totalInAggegation = count($aggregation);
        $total = count($seriesIds);
        $aggregation = $this->getPagedAggregation($aggregation->toArray(), $options['page'], $options['limit']);

        $mostViewed = [];
        foreach ($aggregation as $element) {
            $ids[] = $element['_id'];
            $series = $this->repoSeries->find($element['_id']);
            if ($series) {
                $mostViewed[] = ['series' => $series,
                    'num_viewed' => $element['numView'],
                ];
            }
        }

        //Add series with zero views
        if (count($aggregation) < $options['limit']) {
            if (0 == count($aggregation)) {
                $max = min((1 + $options['page']) * $options['limit'], $total);
                for ($i = ($options['page'] * $options['limit']); $i < $max; ++$i) {
                    $series = $this->repoSeries->find($seriesIds[$i - $totalInAggegation]);
                    if ($series) {
                        $mostViewed[] = ['series' => $series,
                            'num_viewed' => 0,
                        ];
                    }
                }
            } else {
                foreach ($seriesIds as $element) {
                    if (!in_array($element, $ids)) {
                        $series = $this->repoSeries->find($element);
                        if ($series) {
                            $mostViewed[] = ['series' => $series,
                                'num_viewed' => 0,
                            ];
                            if (count($mostViewed) == $options['limit']) {
                                break;
                            }
                        }
                    }
                }
            }
        }

        return [$mostViewed, $total];
    }

    /**
     * Returns an array with the total number of views (all mmobjs) on a certain date range, grouped by hour/day/month/year.
     *
     * If $options['criteria_mmobj'] exists, a query will be executed to filter using the resulting mmobj ids.
     * If $options['criteria_series'] exists, a query will be executed to filter using the resulting series ids.
     */
    public function getTotalViewedGrouped(array $options = [])
    {
        return $this->getGroupedByAggrPipeline($options);
    }

    /**
     * Returns an array with the number of views for a mmobj on a certain date range, grouped by hour/day/month/year.
     */
    public function getTotalViewedGroupedByMmobj(\MongoId $mmobjId, array $options = [])
    {
        return $this->getGroupedByAggrPipeline($options, ['multimediaObject' => $mmobjId]);
    }

    /**
     * Returns an array with the total number of views for a series on a certain date range, grouped by hour/day/month/year.
     */
    public function getTotalViewedGroupedBySeries(\MongoId $seriesId, array $options = [])
    {
        return $this->getGroupedByAggrPipeline($options, ['series' => $seriesId]);
    }

    /**
     * Returns an aggregation pipeline array with all necessary data to form a num_views array grouped by hour/day/...
     *
     * @param mixed $options
     * @param mixed $matchExtra
     */
    public function getGroupedByAggrPipeline($options = [], $matchExtra = [])
    {
        $viewsLogColl = $this->dm->getDocumentCollection($this->collectionName);
        $options = $this->parseOptions($options);

        if (!$matchExtra) {
            if ($options['criteria_series']) {
                $seriesIds = $this->getSeriesIdsWithCriteria($options['criteria_series']);
                $matchExtra['series'] = ['$in' => $seriesIds];
            }
            if ($options['criteria_mmobj']) {
                $mmobjIds = $this->getMmobjIdsWithCriteria($options['criteria_mmobj']);
                $matchExtra['multimediaObject'] = ['$in' => $mmobjIds];
            }
        }

        $pipeline = $this->aggrPipeAddMatch($options['from_date'], $options['to_date'], $matchExtra);
        $pipeline = $this->aggrPipeAddProjectGroupDate($pipeline, $options['group_by']);
        $pipeline[] = ['$sort' => ['_id' => $options['sort']]];

        $aggregation = $viewsLogColl->aggregate($pipeline, ['cursor' => []]);

        $total = count($aggregation);
        $aggregation = $this->getPagedAggregation($aggregation->toArray(), $options['page'], $options['limit']);

        return [$aggregation, $total];
    }

    /**
     * Returns a 'paged' result of the aggregation array.
     *
     * @param aggregation The aggregation array to be paged
     * @param page The page to be returned
     * @param limit The number of elements to be returned
     * @param mixed $page
     * @param mixed $limit
     *
     * @return array aggregation
     */
    public function getPagedAggregation(array $aggregation, $page = 0, $limit = 10)
    {
        $offset = $page * $limit;

        return array_splice($aggregation, $offset, $limit);
    }

    /**
     * Using the aggregation framework create ViewsAggregation collection from ViewsLog.
     *
     * Used by PumukitAggregateCommand
     */
    public function aggregateViewsLog()
    {
        $viewsLogColl = $this->dm->getDocumentCollection('PumukitStatsBundle:ViewsLog');

        $pipeline = [
            [
                '$group' => [
                    '_id' => [
                        'mm' => '$multimediaObject',
                        'day' => [
                            '$dateToString' => [
                                'format' => '%Y-%m-%d',
                                'date' => '$date',
                                // New in MongoDB version 3.6
                                //'timezone' => date_default_timezone_get(),
                            ],
                        ],
                    ],
                    'multimediaObject' => ['$first' => '$multimediaObject'],
                    'series' => ['$first' => '$series'],
                    'date' => ['$first' => '$date'],
                    'numView' => ['$sum' => 1],
                ],
            ],
            [
                '$project' => [
                    '_id' => 0,
                    'multimediaObject' => 1,
                    'series' => 1,
                    'date' => 1,
                    'numView' => 1,
                ],
            ],
            ['$out' => 'ViewsAggregation'],
        ];

        $viewsLogColl->aggregate($pipeline, ['cursor' => [], 'allowDiskUse' => true]);
    }

    /**
     * Returns the pipe with a match.
     *
     * @param mixed $matchExtra
     * @param mixed $pipeline
     */
    private function aggrPipeAddMatch(\DateTime $fromDate = null, \DateTime $toDate = null, $matchExtra = [], $pipeline = [])
    {
        //$filterMath = $this->dm->getFilterCollection()->getFilterCriteria($this->repo->getClassMetadata());

        $date = [];
        if ($fromDate) {
            $fromMongoDate = new \MongoDate($fromDate->format('U'), $fromDate->format('u'));
            $date['$gte'] = $fromMongoDate;
        }
        if ($toDate) {
            $toMongoDate = new \MongoDate($toDate->format('U'), $toDate->format('u'));
            $date['$lte'] = $toMongoDate;
        }
        if (count($date) > 0) {
            $date = ['date' => $date];
        }

        if (count($matchExtra) > 0 || count($date) > 0) {
            //$pipeline[] = array('$match' => array_merge($filterMath, $matchExtra, $date));
            $pipeline[] = ['$match' => array_merge($matchExtra, $date)];
        }

        return $pipeline;
    }

    /**
     * Returns the pipe with a group by date range.
     * It inserts a '$project' before the group to properly get an 'id' to sort with.
     *
     * @param mixed $pipeline
     * @param mixed $groupBy
     */
    private function aggrPipeAddProjectGroupDate($pipeline, $groupBy)
    {
        $mongoProjectDate = $this->getMongoProjectDateArray($groupBy);
        if ('$numView' == $this->sumValue) {
            $pipeline[] = ['$project' => ['numView' => '$numView', 'date' => $mongoProjectDate]];
        } else {
            $pipeline[] = ['$project' => ['date' => $mongoProjectDate]];
        }
        $pipeline[] = ['$group' => ['_id' => '$date',
            'numView' => ['$sum' => $this->sumValue], ],
        ];

        return $pipeline;
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
        $formats = [
            'hour' => '%Y-%m-%dT%HH',
            'day' => '%Y-%m-%d',
            'month' => '%Y-%m',
            'year' => '%Y',
        ];

        $format = $groupBy && isset($formats[$groupBy]) ? $formats[$groupBy] : $formats['month'];

        return [
            '$dateToString' => [
                'format' => $format,
                'date' => $dateField,
                // New in MongoDB version 3.6
                //'timezone' => date_default_timezone_get(),
            ], ];
    }

    /**
     * Returns an array of MongoIds as results from the criteria.
     *
     * @param mixed $criteria
     */
    private function getMmobjIdsWithCriteria($criteria)
    {
        $qb = $this->repo->createStandardQueryBuilder();
        if ($criteria) {
            $qb->addAnd($criteria);
        }

        return $qb->distinct('_id')->getQuery()->execute()->toArray();
    }

    private function getSeriesIdsWithCriteria($criteria)
    {
        $qb = $this->repoSeries->createQueryBuilder();
        if ($criteria) {
            $qb->addAnd($criteria);
        }

        return $qb->distinct('_id')->getQuery()->execute()->toArray();
    }

    /**
     * Parses the options array to add all default options (if not added);.
     */
    private function parseOptions(array $options = [])
    {
        $options['group_by'] = $options['group_by'] ?? 'month';
        $options['limit'] = $options['limit'] ?? 100;
        $options['sort'] = $options['sort'] ?? -1;
        $options['page'] = $options['page'] ?? 0;
        $options['from_date'] = $options['from_date'] ?? null;
        $options['to_date'] = $options['to_date'] ?? null;
        $options['criteria_series'] = $options['criteria_series'] ?? [];
        $options['criteria_mmobj'] = $options['criteria_mmobj'] ?? [];

        return $options;
    }
}
