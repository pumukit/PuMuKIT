<?php

namespace Pumukit\SchemaBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Event\SeriesEvent;
use Pumukit\SchemaBundle\Services\MultimediaObjectEventDispatcherService;

/**
 * NOTE: This listener is to update the seriesTitle field in each
 *       MultimediaObject for MongoDB Search Index purposes.
 *       Do not modify this listener.
 */
class SeriesListener
{
    private $dm;
    private $mmRepo;
    private $mmDispatcher;

    public function __construct(DocumentManager $dm, MultimediaObjectEventDispatcherService $mmDispatcher)
    {
        $this->dm = $dm;
        $this->mmRepo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->mmDispatcher = $mmDispatcher;
    }

    public function postUpdate(SeriesEvent $event)
    {
        $series = $event->getSeries();
        $multimediaObjects = $this->mmRepo->findBySeries($series);
        foreach ($multimediaObjects as $multimediaObject) {
            $updateSeries = $multimediaObject->getSeries();
            $multimediaObject->setSeries($updateSeries);
            $this->dm->persist($multimediaObject);
            $this->mmDispatcher->dispatchUpdate($multimediaObject);
        }
        $this->dm->flush();
    }
}