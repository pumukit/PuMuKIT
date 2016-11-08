<?php

namespace Pumukit\SchemaBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Event\SeriesEvent;

/**
 * NOTE: This listener is to update the seriesTitle field in each
 *       MultimediaObject for MongoDB Search Index purposes.
 *       Do not modify this listener.
 */
class SeriesListener
{
    private $dm;
    private $mmRepo;

    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
        $this->mmRepo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');
    }

    public function postUpdate(SeriesEvent $event)
    {
        $series = $event->getSeries();
        $multimediaObjects = $this->mmRepo->findBySeries($series);
        foreach ($multimediaObjects as $multimediaObject) {
            $multimediaObject->setSeries($series);
            $this->dm->persist($multimediaObject);
        }
        $this->dm->flush();
    }
}
