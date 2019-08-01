<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Utils\Mongo\TextIndexUtils;

class TextIndexService
{
    public function updateSeriesTextIndex(Series $series)
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

    public function updateMultimediaObjectTextIndex(MultimediaObject $multimediaObject)
    {
        $textIndex = [];
        $secondaryTextIndex = [];
        $title = $multimediaObject->getI18nTitle();
        foreach (array_keys($title) as $lang) {
            $text = '';
            $secondaryText = '';
            $mongoLang = TextIndexUtils::getCloseLanguage($lang);

            $text .= $multimediaObject->getTitle($lang);
            $text .= ' | '.$multimediaObject->getKeyword($lang);
            $text .= ' | '.$multimediaObject->getSeriesTitle($lang);
            $secondaryText .= $multimediaObject->getDescription($lang);

            $persons = $multimediaObject->getPeopleByRole();
            foreach ($persons as $key => $person) {
                $secondaryText .= ' | '.$person->getName();
            }

            $textIndex[] = ['indexlanguage' => $mongoLang, 'text' => TextIndexUtils::cleanTextIndex($text)];
            $secondaryTextIndex[] = ['indexlanguage' => $mongoLang, 'text' => TextIndexUtils::cleanTextIndex($secondaryText)];
        }
        $multimediaObject->setTextIndex($textIndex);
        $multimediaObject->setSecondaryTextIndex($secondaryTextIndex);
    }
}
