<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Doctrine\ODM\MongoDB\DocumentManager;

class EmbeddedEventSessionService
{
    private $dm;
    private $repo;
    private $collection;

    /**
     * Constructor.
     */
    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
        $this->repo = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->collection = $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject');
    }

    /**
     * Find current events.
     */
    public function findEventsNow()
    {
        $now = new \MongoDate();

        $pipeline = $this->initPipeline();

        $pipeline[] = array(
            '$match' => array(
                'sessions.start' => array('$exists' => true),
                'sessionEnds' => array('$gte' => $now),
                'sessions.start' => array('$lte' => $now),
            ),
        );

        $pipeline[] = array(
            '$sort' => array(
                'sessions.start' => -1,
            ),
        );

        $this->endPipeline($pipeline);

        $pipeline[] = array('$limit' => 10);

        return $this->collection->aggregate($pipeline)->toArray();
    }

    /**
     * Find today events.
     */
    public function findEventsToday()
    {
        $todayStarts = strtotime(date('Y-m-d H:i:s', mktime(00, 00, 00, date('m'), date('d'), date('Y'))));
        $todayEnds = strtotime(date('Y-m-d H:i:s', mktime(23, 59, 59, date('m'), date('d'), date('Y'))));

        $pipeline = $this->initPipeline();

        $pipeline[] = array(
            '$match' => array('$and' => array(
                array('sessions.start' => array('$gte' => new \MongoDate($todayStarts))),
                array('sessions.start' => array('$lte' => new \MongoDate($todayEnds))),
            )),
        );

        $this->endPipeline($pipeline);

        $pipeline[] = array('$limit' => 20);

        return $this->collection->aggregate($pipeline)->toArray();
    }

    /**
     * Find next events.
     */
    public function findNextEvents()
    {
        $todayEnds = strtotime(date('Y-m-d H:i:s', mktime(23, 59, 59, date('m'), date('d'), date('Y'))));

        $pipeline = $this->initPipeline();

        $pipeline[] = array(
            '$match' => array(
                'sessions.start' => array('$exists' => true),
                'sessions.start' => array('$gte' => new \MongoDate($todayEnds)),
            ),
        );

        $pipeline[] = array(
            '$sort' => array(
                'sessions.start' => 1,
            ),
        );

        $this->endPipeline($pipeline);

        return $this->collection->aggregate($pipeline)->toArray();
    }

    private function initPipeline()
    {
        $pipeline = array();

        $pipeline[] = array(
            '$match' => array(
                'islive' => true,
                'embeddedEvent.display' => true,
                'embeddedEvent.embeddedEventSession' => array('$exists' => true),
            ),
        );

        $pipeline[] = array(
            '$project' => array(
                'multimediaObjectId' => '$_id',
                'event' => '$embeddedEvent',
                'sessions' => '$embeddedEvent.embeddedEventSession',
                'pics' => '$pics',
            ),
        );

        $pipeline[] = array('$unwind' => '$sessions');

        $pipeline[] = array(
            '$project' => array(
                'multimediaObjectId' => '$multimediaObjectId',
                'event' => '$event',
                'sessions' => '$sessions',
                'pics' => '$pics',
                'sessionEnds' => array(
                    '$add' => array(
                        '$sessions.start',
                        array(
                            '$multiply' => array(
                                '$sessions.duration',
                                1000,
                            ),
                        ),
                    ),
                ),
            ),
        );

        return $pipeline;
    }

    private function endPipeline(&$pipeline)
    {
        $pipeline[] = array(
            '$group' => array(
                '_id' => '$multimediaObjectId',
                'data' => array(
                    '$first' => array(
                        'event' => '$event',
                        'session' => '$sessions',
                        'multimediaObjectId' => '$multimediaObjectId',
                        'pics' => '$pics',
                    ),
                ),
            ),
        );

        $pipeline[] = array(
            '$sort' => array(
                'data.session.start' => 1,
            ),
        );
    }
}
