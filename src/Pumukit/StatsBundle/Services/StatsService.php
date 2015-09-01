<?php

namespace Pumukit\StatsBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\StatsBundle\Document\ViewsLog;
use Pumukit\WebTVBundle\Event\ViewedEvent;

class StatsService
{
    private $dm;
    private $repo;

    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
        $this->repo = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
    }


    private function doGetMostViewed(array $criteria = array(), $days = 30, $limit = 3)
    {
        $fromDate = new \DateTime(sprintf("-%s days", $days));
        $fromMongoDate = new \MongoDate($fromDate->format('U'), $fromDate->format('u'));
        $viewsLogColl = $this->dm->getDocumentCollection('PumukitStatsBundle:ViewsLog');

        $pipeline = array(
            array('$match' => array('date' => array('$gte' => $fromMongoDate))),
            array('$group' => array('_id' => '$multimediaObject', 'numView' => array('$sum' => 1))),
            array('$sort' => array('numView' => -1)),
            array('$limit' => $limit*2 ), //Get more elements due to tags post-filter.
        );

        $aggregation = $viewsLogColl->aggregate($pipeline);

        $mostViewed = array();

        
        foreach($aggregation as $element) {
            $criteria['_id'] = $element['_id'];
            $multimediaObject = $this->repo->findBy($criteria, null, 1);

            if ($multimediaObject) {
                $mostViewed[] = $multimediaObject[0];
                if (0 == --$limit) break;
            }
        }

        return $mostViewed;
    }
    
    public function getMostViewed(array $tags, $days = 30, $limit = 3)
    {
        $criteria = array();
        if ($tags) $criteria['tags.cod'] = array('$all' => $tags);        
        return $this->doGetMostViewed($criteria, $days, $limit);
    }

    public function getMostViewedUsingFilters($days = 30, $limit = 3)
    {
        $filters = $this->dm->getFilterCollection()->getFilterCriteria($this->repo->getClassMetadata());
        return $this->doGetMostViewed($filters, $days, $limit);
    }
}