<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Link;
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
     *
     * @param MultimediaObject $multimediaObject
     * @param Link $link
     * @return MultimediaObject
     */
    public function addLinkToMultimediaObject(MultimediaObject $multimediaObject, Link $link)
    {
        $multimediaObject->addLink($link);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        // NOTE Workaround to fix embedded documents modifications
        $this->dm->clear(get_class($multimediaObject));

        return $this->dm->find('PumukitSchemaBundle:MultimediaObject', $multimediaObject->getId());
    }

    /**
     * Update Link in Multimedia Object
     *
     * @param MultimediaObject $multimediaObject
     * @return MultimediaObject
     */
    public function updateLinkInMultimediaObject(MultimediaObject $multimediaObject)
    {
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        // NOTE Workaround to fix embedded documents modifications
        $this->dm->clear(get_class($multimediaObject));

        return $this->dm->find('PumukitSchemaBundle:MultimediaObject', $multimediaObject->getId());
    }

    /**
     * Remove Link from Multimedia Object
     *
     * @param MultimediaObject $multimediaObject
     * @param Link $link
     * @return MultimediaObject
     */
    public function removeLinkFromMultimediaObject(MultimediaObject $multimediaObject, Link $link)
    {
        $multimediaObject->removeLinkById($link->getId());
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        // NOTE Workaround to fix embedded documents modifications
        $this->dm->clear(get_class($multimediaObject));

        return $this->dm->find('PumukitSchemaBundle:MultimediaObject', $multimediaObject->getId());
    }

    /**
     * Up Link in Multimedia Object
     *
     * @param MultimediaObject $multimediaObject
     * @param Link $link
     * @return MultimediaObject
     */
    public function upLinkInMultimediaObject(MultimediaObject $multimediaObject, Link $link)
    {
        $multimediaObject->upLinkById($link->getId());
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        // NOTE Workaround to fix embedded documents modifications
        $this->dm->clear(get_class($multimediaObject));

        return $this->dm->find('PumukitSchemaBundle:MultimediaObject', $multimediaObject->getId());
    }

    /**
     * Down Link in Multimedia Object
     *
     * @param MultimediaObject $multimediaObject
     * @param Link $link
     * @return MultimediaObject
     */
    public function downLinkInMultimediaObject(MultimediaObject $multimediaObject, Link $link)
    {
        $multimediaObject->downLinkById($link->getId());
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        // NOTE Workaround to fix embedded documents modifications
        $this->dm->clear(get_class($multimediaObject));

        return $this->dm->find('PumukitSchemaBundle:MultimediaObject', $multimediaObject->getId());
    }
}
