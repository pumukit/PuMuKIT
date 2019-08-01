<?php

namespace Pumukit\SchemaBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Event\SeriesEvent;
use Pumukit\SchemaBundle\Services\TextIndexService;

/**
 * NOTE: This listener is to update the seriesTitle field in each
 *       MultimediaObject for MongoDB Search Index purposes.
 *       Do not modify this listener.
 */
class SeriesListener
{
    private $dm;
    private $mmRepo;
    private $textIndexService;

    public function __construct(DocumentManager $dm, TextIndexService $textIndexService)
    {
        $this->dm = $dm;
        $this->mmRepo = $dm->getRepository(MultimediaObject::class);
        $this->textIndexService = $textIndexService;
    }

    public function postUpdate(SeriesEvent $event)
    {
        $series = $event->getSeries();
        $this->updateEmbeddedSeriesTitle($series);
        $this->updateTextIndex($series);
        $this->dm->flush();
    }

    public function updateEmbeddedSeriesTitle(Series $series)
    {
        $multimediaObjects = $this->mmRepo->findBySeries($series);
        foreach ($multimediaObjects as $multimediaObject) {
            $multimediaObject->setSeries($series);
            $this->dm->persist($multimediaObject);
        }
    }

    public function updateTextIndex(Series $series)
    {
        $this->textIndexService->updateSeriesTextIndex($series);
    }
}
