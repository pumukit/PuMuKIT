<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\BasePlayerBundle\Event\ViewedEvent;

class MultimediaObjectViews
{
    /** @var DocumentManager */
    private $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    public function onMultimediaObjectViewed(ViewedEvent $event): void
    {
        $track = $event->getTrack();
        $multimediaObject = $event->getMultimediaObject();

        $multimediaObject->incNumview();
        $track && $track->incNumview();
        $this->documentManager->flush();
    }
}
