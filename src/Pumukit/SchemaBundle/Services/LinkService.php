<?php

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Link;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class LinkService
{
    private $dm;
    private $dispatcher;

    public function __construct(DocumentManager $documentManager, LinkEventDispatcherService $dispatcher)
    {
        $this->dm = $documentManager;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Add Link to Multimedia Object.
     *
     * @param MultimediaObject $multimediaObject
     * @param Link             $link
     *
     * @return MultimediaObject
     */
    public function addLinkToMultimediaObject(MultimediaObject $multimediaObject, Link $link)
    {
        $multimediaObject->addLink($link);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();
        $this->dispatcher->dispatchCreate($multimediaObject, $link);

        // NOTE Workaround to fix embedded documents modifications
        $this->dm->clear(get_class($multimediaObject));
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return $this->dm->find(MultimediaObject::class, $multimediaObject->getId());
    }

    /**
     * Update Link in Multimedia Object.
     *
     * @param MultimediaObject $multimediaObject
     * @param Link             $link
     *
     * @return MultimediaObject
     */
    public function updateLinkInMultimediaObject(MultimediaObject $multimediaObject, Link $link)
    {
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $this->dispatcher->dispatchUpdate($multimediaObject, $link);

        // NOTE Workaround to fix embedded documents modifications
        $this->dm->clear(get_class($multimediaObject));

        return $this->dm->find(MultimediaObject::class, $multimediaObject->getId());
    }

    /**
     * Remove Link from Multimedia Object.
     *
     * @param MultimediaObject $multimediaObject
     * @param string           $linkId
     *
     * @return MultimediaObject
     */
    public function removeLinkFromMultimediaObject(MultimediaObject $multimediaObject, $linkId)
    {
        $link = $multimediaObject->getLinkById($linkId);

        $multimediaObject->removeLinkById($linkId);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $this->dispatcher->dispatchDelete($multimediaObject, $link);

        // NOTE Workaround to fix embedded documents modifications
        $this->dm->clear(get_class($multimediaObject));

        return $this->dm->find(MultimediaObject::class, $multimediaObject->getId());
    }

    /**
     * Up Link in Multimedia Object.
     *
     * @param MultimediaObject $multimediaObject
     * @param string           $linkId
     *
     * @return MultimediaObject
     */
    public function upLinkInMultimediaObject(MultimediaObject $multimediaObject, $linkId)
    {
        $multimediaObject->upLinkById($linkId);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        // NOTE Workaround to fix embedded documents modifications
        $this->dm->clear(get_class($multimediaObject));

        return $this->dm->find(MultimediaObject::class, $multimediaObject->getId());
    }

    /**
     * Down Link in Multimedia Object.
     *
     * @param MultimediaObject $multimediaObject
     * @param string           $linkId
     *
     * @return MultimediaObject
     */
    public function downLinkInMultimediaObject(MultimediaObject $multimediaObject, $linkId)
    {
        $multimediaObject->downLinkById($linkId);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        // NOTE Workaround to fix embedded documents modifications
        $this->dm->clear(get_class($multimediaObject));

        return $this->dm->find(MultimediaObject::class, $multimediaObject->getId());
    }
}
