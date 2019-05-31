<?php

namespace Pumukit\NewAdminBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Utils\Mongo\TextIndexUtils;
use Pumukit\SchemaBundle\Utils\Search\SearchUtils;

class SeriesSearchService
{
    private $dm;

    /**
     * SeriesSearchService constructor.
     *
     * @param DocumentManager $documentManager
     */
    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
    }

    /**
     * @param        $reqCriteria
     * @param bool   $searchInObjects
     * @param string $locale
     *
     * @return array
     */
    public function processCriteria($reqCriteria, $searchInObjects = false, $locale = 'en')
    {
        $new_criteria = array();

        foreach ($reqCriteria as $property => $value) {
            if (('search' === $property) && ('' !== $value)) {
                if ($searchInObjects) {
                    $mmRepo = $this->dm->getRepository(MultimediaObject::class);
                    $ids = $mmRepo->getIdsWithSeriesTextOrId($value, 100, 0, $locale)->toArray();
                    $ids[] = $value;

                    if (preg_match('/^[0-9a-z]{24}$/', $value)) {
                        $ids[] = $value;
                    }

                    $new_criteria['$or'] = $this->getSearchCriteria(
                        $value,
                        array(array('_id' => array('$in' => $ids))),
                        $locale
                    );
                } else {
                    $base = array();
                    if (preg_match('/^[0-9a-z]{24}$/', $value)) {
                        $base[] = array('_id' => $value);
                    }
                    $new_criteria['$or'] = $this->getSearchCriteria(
                        $value,
                        $base,
                        $locale
                    );
                }
            } elseif (('date' == $property) && ('' !== $value)) {
                $new_criteria += $this->processDates($value);
            } elseif (('announce' === $property) && ('' !== $value)) {
                if ('true' === $value) {
                    $new_criteria[$property] = true;
                } elseif ('false' === $value) {
                    $new_criteria[$property] = false;
                }
            } elseif (('_id' === $property) && ('' !== $value)) {
                $new_criteria['_id'] = $value;
            } elseif (('title' === $property) && ('' !== $value)) {
                $new_criteria['title.'.$locale] = SearchUtils::generateRegexExpression($value);
            } elseif (('subtitle' === $property) && ('' !== $value)) {
                $new_criteria['subtitle.'.$locale] = SearchUtils::generateRegexExpression($value);
            } elseif ('playlist.multimedia_objects' === $property && ('' !== $value)) {
                $new_criteria['playlist.multimedia_objects'] = array('$size' => 0);
            }
        }

        return $new_criteria;
    }

    /**
     * @param $value
     *
     * @return array
     */
    private function processDates($value)
    {
        $criteria = array();
        $date_from = null;
        $date_to = null;

        if ('' !== $value['from']) {
            $date_from = new \DateTime($value['from']);
        }
        if ('' !== $value['to']) {
            $date_to = new \DateTime($value['to']);
        }

        if (('' !== $value['from']) && ('' !== $value['to'])) {
            $criteria['public_date'] = array('$gte' => $date_from, '$lt' => $date_to);
        } elseif ('' !== $value['from']) {
            $criteria['public_date'] = array('$gte' => $date_from);
        } elseif ('' !== $value['to']) {
            $criteria['public_date'] = array('$lt' => $date_to);
        }

        return $criteria;
    }

    /**
     * @param       $text
     * @param array $base
     * @param       $locale
     *
     * @return array
     */
    private function getSearchCriteria($text, array $base = array(), $locale = 'en')
    {
        $text = trim($text);
        if ((false !== strpos($text, '*')) && (false === strpos($text, ' '))) {
            $text = str_replace('*', '.*', $text);
            $text = SearchUtils::scapeTildes($text);
            $mRegex = new \MongoRegex("/$text/i");
            $base[] = array(('title.'.$locale) => $mRegex);
            $base[] = array('people.people.name' => $mRegex);
        } else {
            $base[] = array('$text' => array(
                '$search' => TextIndexUtils::cleanTextIndex($text),
                '$language' => TextIndexUtils::getCloseLanguage($locale),
            ));
        }

        return $base;
    }
}
