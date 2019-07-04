<?php

namespace Pumukit\SchemaBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Event\SeriesEvent;
use Pumukit\SchemaBundle\Utils\Mongo\TextIndexUtils;

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
        $this->mmRepo = $dm->getRepository(MultimediaObject::class);
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
        $textIndex = [];
        $secondaryTextIndex = [];
        $title = $series->getI18nTitle();
        foreach (array_keys($title) as $lang) {
            $text = '';
            $secondaryText = '';
            $mongoLang = TextIndexUtils::getCloseLanguage($lang);

            $text .= $series->getTitle($lang);
            $text .= ' | '.$series->getKeyword($lang);
            $secondaryText .= $series->getDescription($lang);

            $textIndex[] = ['indexlanguage' => $mongoLang, 'text' => TextIndexUtils::cleanTextIndex($text)];
            $secondaryTextIndex[] = ['indexlanguage' => $mongoLang, 'text' => TextIndexUtils::cleanTextIndex($secondaryText)];
        }
        $series->setTextIndex($textIndex);
        $series->setSecondaryTextIndex($secondaryTextIndex);
    }
}
