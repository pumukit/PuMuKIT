<?php

namespace Pumukit\SchemaBundle\Services;

use Symfony\Component\HttpFoundation\File\File;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Link;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\DocumentManager;

class LinkService
{
    private $dm;

    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
    }

    /**
     * Add Link to Multimedia Object
     */
    public function addLinkToMultimediaObject(MultimediaObject $multimediaObject, Link $link)
    {
        $multimediaObject->addLink($link);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return $multimediaObject;
    }

    /**
     * Update Link in Multimedia Object
     */
    public function updateLinkInMultimediaObject(MultimediaObject $multimediaObject, Link $link)
    {
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return $multimediaObject;
    }
    
    /**
     * Remove Link from Multimedia Object
     */
    public function removeLinkFromMultimediaObject(MultimediaObject $multimediaObject, $linkId)
    {
        $multimediaObject->removeLinkById($linkId);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return $multimediaObject;
    }

    /**
     * Up Link in Multimedia Object
     */
    public function upLinkInMultimediaObject(MultimediaObject $multimediaObject, $linkId)
    {
        $multimediaObject->upLinkById($linkId);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return $multimediaObject;
    }

    /**
     * Down Link in Multimedia Object
     */
    public function downLinkInMultimediaObject(MultimediaObject $multimediaObject, $linkId)
    {
        $multimediaObject->downLinkById($linkId);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return $multimediaObject;
    }
}