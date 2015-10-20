<?php

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\WebTVBundle\Event\WebTVEvents;
use Pumukit\WebTVBundle\Event\ViewedEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class MultimediaObjectService
{
    private $dm;
    private $repo;

    public function __construct(DocumentManager $documentManager, EventDispatcherInterface $dispatcher)
    {
        $this->dm = $documentManager;
        $this->repo = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->dispatcher = $dispatcher;
    }

    public function incNumView(MultimediaObject $multimediaObject, Track $track = null)
    {
        $multimediaObject->incNumview();
        $track && $track->incNumview();
        $this->dm->persist($multimediaObject);
        $this->dm->flush();
    }

    public function dispatch(MultimediaObject $multimediaObject, Track $track = null)
    {
        $event = new ViewedEvent($multimediaObject, $track);
        $this->dispatcher->dispatch(WebTVEvents::MULTIMEDIAOBJECT_VIEW, $event);
    }

}
