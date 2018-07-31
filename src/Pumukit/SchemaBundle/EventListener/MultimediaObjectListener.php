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
        $this->updateTextIndex($multimediaObject);
        $this->dm->flush();
    }

    /**
     * @param $multimediaObject
     */
    public function updateTextIndex($multimediaObject)
    {
        $textindex = array();
        $secondarytextindex = array();
        $title = $multimediaObject->getI18nTitle();
        $text = '';
        $secondarytext = '';
        $mongo_langs = array('da', 'nl', 'en', 'fi', 'fr', 'de', 'hu', 'it', 'nb', 'pt', 'ro', 'ru', 'es', 'sv', 'tr', 'ara', 'prs', 'pes', 'urd', 'zhs', 'zht');
        foreach (array_keys($title) as $lang) {
            if (in_array($lang, $mongo_langs)) {
                if ($multimediaObject->getTitle($lang)) {
                    $text = $multimediaObject->getTitle($lang);
                }
                if ($multimediaObject->getKeywords($lang)) {
                    $text = $text.' | '.$multimediaObject->getKeywords($lang);
                }
                if ($multimediaObject->getDescription($lang)) {
                    $secondarytext = $multimediaObject->getDescription($lang);
                }
                $textindex[] = array('indexlanguage' => $lang, 'text' => $this->sanitizeText($text));
                $secondarytextindex[] = array('indexlanguage' => $lang, 'text' => $this->sanitizeText($secondarytext));
            }
        }
        $multimediaObject->setTextIndex($textindex);
        $multimediaObject->setSecondaryTextIndex($secondarytextindex);
    }

    /**
     * @param $text
     *
     * @return string
     */
    public function sanitizeText($text)
    {
        $unwanted_array = array(
          'Š' => 'S',
          'š' => 's',
          'Ž' => 'Z',
          'ž' => 'z',
          'À' => 'A',
          'Á' => 'A',
          'Â' => 'A',
          'Ã' => 'A',
          'Ä' => 'A',
          'Å' => 'A',
          'Æ' => 'A',
          'Ç' => 'C',
          'È' => 'E',
          'É' => 'E',
          'Ê' => 'E',
          'Ë' => 'E',
          'Ì' => 'I',
          'Í' => 'I',
          'Î' => 'I',
          'Ï' => 'I',
          'Ñ' => 'N',
          'Ò' => 'O',
          'Ó' => 'O',
          'Ô' => 'O',
          'Õ' => 'O',
          'Ö' => 'O',
          'Ø' => 'O',
          'Ù' => 'U',
          'Ú' => 'U',
          'Û' => 'U',
          'Ü' => 'U',
          'Ý' => 'Y',
          'Þ' => 'B',
          'ß' => 'Ss',
          'à' => 'a',
          'á' => 'a',
          'â' => 'a',
          'ã' => 'a',
          'ä' => 'a',
          'å' => 'a',
          'æ' => 'a',
          'ç' => 'c',
          'è' => 'e',
          'é' => 'e',
          'ê' => 'e',
          'ë' => 'e',
          'ì' => 'i',
          'í' => 'i',
          'î' => 'i',
          'ï' => 'i',
          'ð' => 'o',
          'ñ' => 'n',
          'ò' => 'o',
          'ó' => 'o',
          'ô' => 'o',
          'õ' => 'o',
          'ö' => 'o',
          'ø' => 'o',
          'ù' => 'u',
          'ú' => 'u',
          'û' => 'u',
          'ý' => 'y',
          'þ' => 'b',
          'ÿ' => 'y',
        );

        return strtolower(strtr($text, $unwanted_array));
    }
}
