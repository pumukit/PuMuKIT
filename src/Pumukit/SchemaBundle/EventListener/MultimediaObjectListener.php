<?php

namespace Pumukit\SchemaBundle\EventListener;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Doctrine\ODM\MongoDB\DocumentManager;

class MultimediaObjectListener
{
    private $dm;

    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    /**
     * @param $event
     */
    public function postUpdate($event)
    {
        $multimediaObject = $event->getMultimediaObject();

        if ($multimediaObject->getProperty('opencast')) {
            $multimediaObject->setType(MultimediaObject::TYPE_VIDEO);
        } elseif ($multimediaObject->getProperty('externalplayer')) {
            $multimediaObject->setType(MultimediaObject::TYPE_EXTERNAL);
        } elseif ($track = $multimediaObject->getMaster(false)) {
            if ($track->isOnlyAudio()) {
                $multimediaObject->setType(MultimediaObject::TYPE_AUDIO);
            } else {
                $multimediaObject->setType(MultimediaObject::TYPE_VIDEO);
            }
        } else {
            $multimediaObject->setType(MultimediaObject::TYPE_UNKNOWN);
        }

        $textindex = array();
        $title = $multimediaObject->getI18nTitle();
        //FIXME array_keys($title) fail if english title empty
        foreach (array_keys($title) as $lang) {
            if ($multimediaObject->getTitle($lang)) {
                $text = $multimediaObject->getTitle($lang);
            }
            if ($multimediaObject->getDescription($lang)) {
                $text = $text . " | " . $multimediaObject->getDescription($lang);
            }
            if ($multimediaObject->getKeywords($lang)) {
                $text =  $text . " | " . $multimediaObject->getKeywords($lang);
            }
            $textindex[] =  array("indexlanguage" => $lang, "text" => $text);
        }
        $multimediaObject->setTextIndex([$textindex]);

        $this->dm->flush();
    }
}
