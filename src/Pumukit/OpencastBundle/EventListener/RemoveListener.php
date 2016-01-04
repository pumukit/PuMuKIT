<?php

namespace Pumukit\OpencastBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\OpencastBundle\Services\ClientService;
use Pumukit\SchemaBundle\Event\MultimediaObjectEvent;
use Pumukit\SchemaBundle\Event\TrackEvent;

class RemoveListener
{
    private $dm;
    private $clientService;

    public function __construct(DocumentManager $documentManager, ClientService $clientService)
    {
        $this->dm = $documentManager;
        $this->clientService = $clientService;
    }

    public function onMultimediaObjectDelete(MultimediaObjectEvent $event)
    {
        $multimediaObject = $event->getMultimediaObject();
        if ($mediaPackageId = $multimediaObject->getProperty('opencast')) {
            $output = $this->clientService->applyWorkflowToMediaPackages(array($mediaPackageId));
            if (!$output) {
                throw new \Exception('Error on deleting Opencast media package "'
                                     .$mediaPackageId.'" from archive.');
            }
        }
    }
}