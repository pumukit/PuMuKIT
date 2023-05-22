<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\Regex;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Utils\Mongo\TextIndexUtils;
use Pumukit\SchemaBundle\Utils\Search\SearchUtils;

class SeriesSearchService
{
    private $dm;

    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
    }

    public function processCriteria(array $reqCriteria, bool $searchInObjects = false, string $locale = 'en'): array
    {
        $new_criteria = [];

        foreach ($reqCriteria as $property => $value) {
            if (('search' === $property) && ('' !== $value)) {
                if ($searchInObjects) {
                    $mmRepo = $this->dm->getRepository(MultimediaObject::class);
                    $ids = $mmRepo->getIdsWithSeriesTextOrId($value, 100, 0, $locale);
                    $ids[] = $value;

                    if (preg_match('/^[0-9a-z]{24}$/', $value)) {
                        $ids[] = $value;
                    }

                    $new_criteria['$or'] = $this->getSearchCriteria(
                        $value,
                        [['_id' => ['$in' => $ids]]],
                        $locale
                    );
                } else {
                    $base = [];
                    if (preg_match('/^[0-9a-z]{24}$/', $value)) {
                        $base[] = ['_id' => $value];
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
                $new_criteria['playlist.multimedia_objects'] = ['$size' => 0];
            }
        }

        return $new_criteria;
    }

    private function processDates(array $value): array
    {
        $criteria = [];
        $date_from = null;
        $date_to = null;

        if ('' !== $value['from']) {
            $date_from = new \DateTime($value['from']);
        }
        if ('' !== $value['to']) {
            $value['to'] .= ' 23:59:59';
            $date_to = new \DateTime($value['to']);
        }

        if (('' !== $value['from']) && ('' !== $value['to'])) {
            $criteria['public_date'] = ['$gte' => $date_from, '$lt' => $date_to];
        } elseif ('' !== $value['from']) {
            $criteria['public_date'] = ['$gte' => $date_from];
        } elseif ('' !== $value['to']) {
            $criteria['public_date'] = ['$lt' => $date_to];
        }

        return $criteria;
    }

    private function getSearchCriteria(string $text, array $base = [], string $locale = 'en'): array
    {
        $text = trim($text);
        if ((false !== strpos($text, '*')) && (false === strpos($text, ' '))) {
            $text = str_replace('*', '.*', $text);
            $text = SearchUtils::scapeTildes($text);
            $mRegex = new Regex($text, 'i');
            $base[] = [('title.'.$locale) => $mRegex];
            $base[] = ['people.people.name' => $mRegex];
        } else {
            $base[] = ['$text' => [
                '$search' => TextIndexUtils::cleanTextIndex($text),
                '$language' => TextIndexUtils::getCloseLanguage($locale),
            ]];
        }

        return $base;
    }
}
