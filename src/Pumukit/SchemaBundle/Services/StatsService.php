<?php

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class StatsService
{
    private $dm;
    private $repoMmobj;

    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
        $this->repoMmobj = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
    }

    public function getGlobalStats($groupBy = 'month', $sort = -1)
    {
        $dmColl = $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject');
        $dmRepo = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');

        $mongoProjectDate = $this->getMongoProjectDateArray($groupBy, '$record_date');

        $pipeline = array();
        $criteria = array(
            'type' => ['$ne' => MultimediaObject::TYPE_LIVE],
            'status' => array('$ne' => MultimediaObject::STATUS_PROTOTYPE),
        );
        $pipeline[] = array('$match' => $criteria);

        $this->dm->getFilterCollection()->enable('backoffice');
        $criteria = $this->dm->getFilterCollection()->getFilterCriteria($dmRepo->getClassMetadata());
        if ($criteria) {
            $pipeline[] = array('$match' => $criteria);
        }
        $pipeline[] = array(
            '$project' => array(
                'date' => $mongoProjectDate,
                'duration' => '$duration',
                'size' => array('$sum' => '$tracks.size'),
            ),
        );
        $pipeline[] = array(
            '$group' => array(
                '_id' => '$date',
                'num' => array('$sum' => 1),
                'duration' => array('$sum' => '$duration'),
                'size' => array('$sum' => '$size'),
            ),
        );
        $pipeline[] = array('$sort' => array('_id' => $sort));

        $aggregation = $dmColl->aggregate($pipeline);

        return $aggregation->toArray();
    }

    public function getMmobjRecordedGroupedBy($fromDate = null, $toDate = null, $limit = 100, $page = 0, $criteria = array(), $sort = -1, $groupBy = 'month')
    {
        $dmColl = $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject');
        $mongoGroup = array('numMmobjs' => array('$sum' => 1));

        $aggregation = $this->getAggrRecordedGroupedBy($dmColl, $mongoGroup, 'record_date', $fromDate, $toDate, $limit, $page, $criteria, $sort, $groupBy);

        return $aggregation->toArray();
    }

    public function getSeriesRecordedGroupedBy($fromDate = null, $toDate = null, $limit = 100, $page = 0, $criteria = array(), $sort = -1, $groupBy = 'month')
    {
        $dmColl = $this->dm->getDocumentCollection('PumukitSchemaBundle:Series');
        $mongoGroup = array('numSeries' => array('$sum' => 1));

        $aggregation = $this->getAggrRecordedGroupedBy($dmColl, $mongoGroup, 'public_date', $fromDate, $toDate, $limit, $page, $criteria, $sort, $groupBy);

        return $aggregation->toArray();
    }

    public function getHoursRecordedGroupedBy($fromDate = null, $toDate = null, $limit = 100, $page = 0, $criteria = array(), $sort = -1, $groupBy = 'month')
    {
        $dmColl = $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject');
        $mongoGroup = array('seconds' => array('$sum' => '$duration'));

        $aggregation = $this->getAggrRecordedGroupedBy($dmColl, $mongoGroup, 'record_date', $fromDate, $toDate, $limit, $page, $criteria, $sort, $groupBy);

        return $aggregation->toArray();
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
            $mongoProjectDate[] = array('$substr' => array($dateField, 0, 2));
            $mongoProjectDate[] = 'T';
            // no break
        case 'day':
            $mongoProjectDate[] = array('$substr' => array($dateField, 8, 2));
            $mongoProjectDate[] = '-';
            // no break
        default: //If it doesn't exists, it's 'month'
        case 'month':
            $mongoProjectDate[] = array('$substr' => array($dateField, 5, 2));
            $mongoProjectDate[] = '-';
            // no break
        case 'year':
            $mongoProjectDate[] = array('$substr' => array($dateField, 0, 4));
            break;
        }

        return array('$concat' => array_reverse($mongoProjectDate));
    }

    /**
     * Returns an aggregation of objects grouped by date.
     */
    private function getAggrRecordedGroupedBy($dmColl, $mongoGroup, $dateName = 'record_date', $fromDate = null, $toDate = null, $limit = 100, $page = 0, $criteria = array(), $sort = -1, $groupBy = 'month')
    {
        $matchExtra = array();
        if (!empty($criteria)) {
            $mmobjIds = $this->getIdsWithCriteria($criteria, $this->repoMmobj);
            $matchExtra['_id'] = array('$in' => $mmobjIds);
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

        $pipeline[] = array('$match' => array_merge(
            $matchExtra,
            array($dateName => array('$gte' => $fromMongoDate, '$lte' => $toMongoDate))),
        );
        $mongoProjectDate = $this->getMongoProjectDateArray($groupBy, '$'.$dateName);
        $pipeline[] = array('$project' => array('date' => $mongoProjectDate, 'duration' => '$duration'));
        $pipeline[] = array('$group' => array_merge(array('_id' => '$date'), $mongoGroup));
        $pipeline[] = array('$sort' => array('_id' => $sort));
        $pipeline[] = array('$skip' => $page * $limit);
        $pipeline[] = array('$limit' => $limit);
        $aggregation = $dmColl->aggregate($pipeline, array('cursor' => array()));

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
