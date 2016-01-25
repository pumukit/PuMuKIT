<?php

namespace Pumukit\VideoEditorBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\WebTVBundle\Event\ViewedEvent;


class AnnotationsAPIListenerService
{
    private $dm;
    private $repo;
    private $dispatcher;

    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
        $this->repo = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
    }


    public function onAnnotationsAPIUpdate($event)
    {
        $mmobjId = $event->getMultimediaObject();
        $mmobj = $this->repo->find($mmobjId);
        $softDuration = 100;
        $mmobj->setProperty('soft-editing-duration', $softDuration);
        $this->dm->persist($mmobj);
        $this->dm->flush();
    }
}
