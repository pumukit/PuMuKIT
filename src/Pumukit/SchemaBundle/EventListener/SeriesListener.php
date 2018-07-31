<?php

namespace Pumukit\SchemaBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Event\SeriesEvent;

/**
 * NOTE: This listener is to update the seriesTitle field in each
 *       MultimediaObject for MongoDB Search Index purposes.
 *       Do not modify this listener.
 */
class SeriesListener
{
    private $dm;
    private $mmRepo;

    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
        $this->mmRepo = $dm->getRepository('PumukitSchemaBundle:MultimediaObject');
    }

    public function postUpdate(SeriesEvent $event)
    {
        $series = $event->getSeries();
        $multimediaObjects = $this->mmRepo->findBySeries($series);
        foreach ($multimediaObjects as $multimediaObject) {
            $multimediaObject->setSeries($series);
            $this->dm->persist($multimediaObject);
        }
        $this->updateTextIndex($series);
        $this->dm->flush();
    }

    /**
     * @param $series
     */
    public function updateTextIndex($series)
    {
        $textindex = array();
        $secondarytextindex = array();
        $title = $series->getI18nTitle();
        $text = '';
        $secondarytext = '';
        $mongo_langs = array('da', 'nl', 'en', 'fi', 'fr', 'de', 'hu', 'it', 'nb', 'pt', 'ro', 'ru', 'es', 'sv', 'tr', 'ara', 'prs', 'pes', 'urd', 'zhs', 'zht');
        foreach (array_keys($title) as $lang) {
            if (in_array($lang, $mongo_langs)) {
                if ($series->getTitle($lang)) {
                    $text = $series->getTitle($lang);
                }
                if ($series->getKeywords($lang)) {
                    $text = $text.' | '.$series->getKeywords($lang);
                }
                if ($series->getDescription($lang)) {
                    $secondarytext = $series->getDescription($lang);
                }
                $textindex[] = array('indexlanguage' => $lang, 'text' => $this->sanitizeText($text));
                $secondarytextindex[] = array('indexlanguage' => $lang, 'text' => $this->sanitizeText($secondarytext));
            }
        }
        $series->setTextIndex($textindex);
        $series->setSecondaryTextIndex($secondarytextindex);
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
