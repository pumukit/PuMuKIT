<?php

namespace Pumukit\OpencastBundle\EventListener;

use Pumukit\OpencastBundle\Services\ClientService;
use Pumukit\SchemaBundle\Event\MultimediaObjectEvent;

class RemoveListener
{
    private $clientService;
    private $deleteArchiveMediaPackage;

    public function __construct(ClientService $clientService, $deleteArchiveMediaPackage = false)
    {
        $this->clientService = $clientService;
        $this->deleteArchiveMediaPackage = $deleteArchiveMediaPackage;
    }

    public function onMultimediaObjectDelete(MultimediaObjectEvent $event)
    {
        if ($this->deleteArchiveMediaPackage) {
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
}