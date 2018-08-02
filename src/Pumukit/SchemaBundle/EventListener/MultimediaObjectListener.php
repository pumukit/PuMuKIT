<?php

namespace Pumukit\SchemaBundle\EventListener;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Utils\Mongo\TextIndexUtils;
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
        $this->updateType($multimediaObject);
        $this->updateTextIndex($multimediaObject);
        $this->dm->flush();
    }

    /**
     * @param $multimediaObject
     */
    public function updateType($multimediaObject)
    {
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
    }

    /**
     * @param $multimediaObject
     */
    public function updateTextIndex($multimediaObject)
    {
        $textIndex = array();
        $secondaryTextIndex = array();
        $title = $multimediaObject->getI18nTitle();
        foreach (array_keys($title) as $lang) {
            $text = '';
            $secondaryText = '';
            $mongoLang = TextIndexUtils::getCloseLanguage($lang);

            $text .= $multimediaObject->getTitle($lang);
            $text .= ' | '.$multimediaObject->getKeyword($lang);
            $text .= ' | '.$multimediaObject->getSeriesTitle($lang);
            $secondaryText .= $multimediaObject->getDescription($lang);

            $textIndex[] = array('indexlanguage' => $mongoLang, 'text' => TextIndexUtils::cleanTextIndex($text));
            $secondaryTextIndex[] = array('indexlanguage' => $mongoLang, 'text' => TextIndexUtils::cleanTextIndex($secondaryText));
        }
        $multimediaObject->setTextIndex($textIndex);
        $multimediaObject->setSecondaryTextIndex($secondaryTextIndex);
    }
}
