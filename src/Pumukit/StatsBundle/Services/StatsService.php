<?php

namespace Pumukit\StatsBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\StatsBundle\Document\ViewsLog;

class StatsService
{
    private $dm;
    private $repo;

    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
        $this->repo = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->repoSeries = $this->dm->getRepository('PumukitSchemaBundle:Series');
    }

    public function doGetMostViewed(array $criteria = array(), $days = 30, $limit = 3)
    {
        $ids = array();
        $fromDate = new \DateTime(sprintf('-%s days', $days));
        $fromMongoDate = new \MongoDate($fromDate->format('U'), $fromDate->format('u'));
        $viewsLogColl = $this->dm->getDocumentCollection('PumukitStatsBundle:ViewsLog');

        $pipeline = array(
            array('$match' => array('date' => array('$gte' => $fromMongoDate))),
            array('$group' => array('_id' => '$multimediaObject', 'numView' => array('$sum' => 1))),
            array('$sort' => array('numView' => -1)),
            array('$limit' => $limit * 2), //Get more elements due to tags post-filter.
        );

        $aggregation = $viewsLogColl->aggregate($pipeline);

        $mostViewed = array();

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
            $criteria['_id'] = array('$nin' => $ids);

            return array_merge($mostViewed, $this->repo->findStandardBy($criteria, null, $limit));
        }

        return $mostViewed;
    }

    public function getMostViewed(array $tags, $days = 30, $limit = 3)
    {
        $criteria = array();
        if ($tags) {
            $criteria['tags.cod'] = array('$all' => $tags);
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
    public function getMmobjsMostViewedByRange(array $criteria = array(), array $options = array())
    {
        $ids = array();

        $viewsLogColl = $this->dm->getDocumentCollection('PumukitStatsBundle:ViewsLog');

        $matchExtra = array();
        if (!empty($criteria)) {
            $mmobjIds = $this->getMmobjIdsWithCriteria($criteria);
            $matchExtra['multimediaObject'] = array('$in' => $mmobjIds);
        }

        $options = $this->parseOptions($options);

        $pipeline = array();
        $pipeline = $this->aggrPipeAddMatch($options['fromDate'], $options['toDate'], $matchExtra);
        $pipeline[] = array('$group' => array('_id' => '$multimediaObject', 'numView' => array('$sum' => 1)));
        $pipeline[] = array('$sort' => array('numView' => $options['sort']));
        $pipeline[] = array('$skip' => $options['page'] * $options['limit']);
        $pipeline[] = array('$limit' => $options['limit']);

        $aggregation = $viewsLogColl->aggregate($pipeline);
        $mostViewed = array();
        foreach ($aggregation as $element) {
            $ids[] = $element['_id'];
            $multimediaObject = $this->repo->find($element['_id']);
            if ($multimediaObject) {
                $mostViewed[] = array('mmobj' => $multimediaObject,
                                      'num_viewed' => $element['numView'],
                );
            }
        }

        return $mostViewed;
    }

    /**
     * Returns an array of series viewed on the given range and its number of views on that range.
     */
    public function getSeriesMostViewedByRange(array $criteria = array(), array $options = array())
    {
        $ids = array();
        $viewsLogColl = $this->dm->getDocumentCollection('PumukitStatsBundle:ViewsLog');

        $matchExtra = array();
        if (!empty($criteria)) {
            $mmobjIds = $this->getMmobjIdsWithCriteria($criteria);
            $matchExtra['multimediaObject'] = array('$in' => $mmobjIds);
        }

        $options = $this->parseOptions($options);

        $pipeline = array();
        $pipeline = $this->aggrPipeAddMatch($options['fromDate'], $options['toDate'], $matchExtra);
        $pipeline[] = array('$group' => array('_id' => '$series', 'numView' => array('$sum' => 1)));
        $pipeline[] = array('$sort' => array('numView' => $options['sort']));
        $pipeline[] = array('$skip' => $options['page'] * $options['limit']);
        $pipeline[] = array('$limit' => $options['limit']);

        $aggregation = $viewsLogColl->aggregate($pipeline);
        $mostViewed = array();
        foreach ($aggregation as $element) {
            $ids[] = $element['_id'];
            $series = $this->repoSeries->find($element['_id']);
            if ($series) {
                $mostViewed[] = array('series' => $series,
                                      'num_viewed' => $element['numView'],
                );
            }
        }

        return $mostViewed;
    }

    /**
     * Returns an array with the total number of views (all mmobjs) on a certain date range, grouped by hour/day/month/year.
     */
    public function getTotalViewedGrouped(array $criteria = array(), array $options = array())
    {
        $aggregation = $this->getGroupedByAggrPipeline($criteria, $options);

        return $aggregation->toArray();
    }

    /**
     * Returns an array with the number of views for a mmobj on a certain date range, grouped by hour/day/month/year.
     */
    public function getTotalViewedGroupedByMmobj(\MongoId $mmobjId, array $criteria = array(), array $options = array())
    {
        $aggregation = $this->getGroupedByAggrPipeline($criteria, $options, array('multimediaObject' => $mmobjId));

        return $aggregation->toArray();
    }

    /**
     * Returns an array with the total number of views for a series on a certain date range, grouped by hour/day/month/year.
     */
    public function getTotalViewedGroupedBySeries(\MongoId $seriesId, array $criteria = array(), array $options = array())
    {
        $aggregation = $this->getGroupedByAggrPipeline($criteria, $options, array('series' => $seriesId));

        return $aggregation->toArray();
    }

    /**
     * Returns an aggregation pipeline array with all necessary data to form a num_views array grouped by hour/day/...
     */
    public function getGroupedByAggrPipeline($criteria = array(), $options = array(), $matchExtra = array())
    {
        $viewsLogColl = $this->dm->getDocumentCollection('PumukitStatsBundle:ViewsLog');

        if (!empty($criteria)) {
            $mmobjIds = $this->getMmobjIdsWithCriteria($criteria);
            $matchExtra['multimediaObject'] = array('$in' => $mmobjIds);
        }

        $options = $this->parseOptions($options);

        $pipeline = $this->aggrPipeAddMatch($options['fromDate'], $options['toDate'], $matchExtra);
        $pipeline = $this->aggrPipeAddProjectGroupDate($pipeline, $options['groupBy']);
        $pipeline[] = array('$sort' => array('_id' => $options['sort']));
        $pipeline[] = array('$skip' => $options['page'] * $options['limit']);
        $pipeline[] = array('$limit' => $options['limit']);

        $aggregation = $viewsLogColl->aggregate($pipeline);

        return $aggregation;
    }

    /**
     * Returns the pipe with a match.
     */
    private function aggrPipeAddMatch(\DateTime $fromDate = null, \DateTime $toDate = null, $matchExtra = array(), $pipeline = array())
    {
        if (!$fromDate) {
            $fromDate = new \DateTime();
            $fromDate->setTime(0, 0, 0);
        }
        if (!$toDate) {
            $toDate = new \DateTime();
        }

        $fromMongoDate = new \MongoDate($fromDate->format('U'), $fromDate->format('u'));
        $toMongoDate = new \MongoDate($toDate->format('U'), $toDate->format('u'));

        $pipeline[] = array('$match' => array_merge($matchExtra, array('date' => array('$gte' => $fromMongoDate,
                                                                                        '$lte' => $toMongoDate, ))),
        );

        return $pipeline;
    }

    /**
     * Returns the pipe with a group by date range.
     * It inserts a '$project' before the group to properly get an 'id' to sort with.
     */
    private function aggrPipeAddProjectGroupDate($pipeline, $groupBy)
    {
        $mongoProjectDate = $this->getMongoProjectDateArray($groupBy);
        $pipeline[] = array('$project' => array('date' => $mongoProjectDate));
        $pipeline[] = array('$group' => array('_id' => '$date',
                                              'numView' => array('$sum' => 1), ),
        );

        return $pipeline;
    }

    /**
     * Returns an array for a mongo $project pipeline to create a date-formatted string with just the required fields.
     * It is used for grouping results in date ranges (hour/day/month/year).
     */
    private function getMongoProjectDateArray($groupBy, $dateField = '$date')
    {
        $mongoProjectDate = array();
        switch ($groupBy) {
            case 'hour':
                $mongoProjectDate[] = 'H';
                $mongoProjectDate[] = array('$substr' => array($dateField,0,2));
                $mongoProjectDate[] = 'T';
            case 'day':
                $mongoProjectDate[] = array('$substr' => array($dateField,8,2));
                $mongoProjectDate[] = '-';
            default: //If it doesn't exists, it's 'month'
            case 'month':
                $mongoProjectDate[] = array('$substr' => array($dateField,5,2));
                $mongoProjectDate[] = '-';
            case 'year':
                $mongoProjectDate[] = array('$substr' => array($dateField,0,4));
                break;
        }

        return array('$concat' => array_reverse($mongoProjectDate));
    }

    /**
     * Returns an array of MongoIds as results from the criteria.
     */
    private function getMmobjIdsWithCriteria($criteria)
    {
        $mmobjIds = $this->repo->createQueryBuilder()->addAnd($criteria)->distinct('_id')->getQuery()->execute()->toArray();

        return $mmobjIds;
    }

    /**
     * Parses the options array to add all default options (if not added);.
     */
    private function parseOptions(array $options = array())
    {
        $options['groupBy'] = isset($options['groupBy']) ? $options['groupBy'] : 'month';
        $options['limit'] = isset($options['limit']) ? $options['limit'] : 100;
        $options['sort'] = isset($options['sort']) ? $options['sort'] : -1;
        $options['page'] = isset($options['page']) ? $options['page'] : 0;
        $options['fromDate'] = isset($options['fromDate']) ? $options['fromDate'] : null;
        $options['toDate'] = isset($options['toDate']) ? $options['toDate'] : null;

        return $options;
    }
}
