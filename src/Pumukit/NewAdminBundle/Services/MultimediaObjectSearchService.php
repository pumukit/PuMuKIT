<?php

namespace Pumukit\NewAdminBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Utils\Mongo\TextIndexUtils;
use Pumukit\SchemaBundle\Utils\Search\SearchUtils;

class MultimediaObjectSearchService
{
    /**
     * @param $reqCriteria
     * @param $locale
     *
     * @return array
     */
    public function processMMOCriteria($reqCriteria, $locale = 'en')
    {
        $new_criteria = ['status' => ['$ne' => MultimediaObject::STATUS_PROTOTYPE]];
        $bAnnounce = '';
        $bChannel = '';
        $bPerson = false;
        $bRole = false;
        $bStatus = false;
        $personName = '';
        $roleCode = '';
        $sChannelValue = false;

        foreach ($reqCriteria as $property => $value) {
            if (('search' === $property) && ('' !== $value)) {
                $new_criteria['$or'] = $this->getSearchCriteria(
                    $value,
                    [['_id' => ['$in' => [$value]]]],
                    $locale
                );
            } elseif (('person_name' === $property) && ('' !== $value)) {
                $personName = $value;
                $bPerson = true;
            } elseif (('person_role' === $property) && ('' !== $value) && ('all' !== $value)) {
                $roleCode = $value;
                $bRole = true;
            } elseif (('channel' === $property) && ('' !== $value)) {
                if ('all' !== $value) {
                    $bChannel = true;
                    $sChannelValue = $value;
                }
            } elseif (('announce' === $property) && ('' !== $value)) {
                if ('true' === $value) {
                    $bAnnounce = true;
                } elseif ('false' === $value) {
                    $bAnnounce = false;
                }
            } elseif (('date' === $property) && ('' !== $value)) {
                $new_criteria += $this->processDates($value);
            } elseif (('status' === $property) && ('' !== $value)) {
                $bStatus = true;
                $aStatus = $value;
            }
        }

        $new_criteria['type'] = ['$ne' => MultimediaObject::TYPE_LIVE];

        if ('' !== $bAnnounce) {
            if (('' !== $bChannel) && $bChannel && $bAnnounce) {
                $new_criteria += ['$and' => [['tags.cod' => $sChannelValue], ['tags.cod' => 'PUDENEW']]];
            } elseif (('' !== $bChannel) && $bChannel) {
                $new_criteria += ['$and' => [['tags.cod' => $sChannelValue]]];
            } elseif ($bAnnounce) {
                $new_criteria += ['$and' => [['tags.cod' => 'PUDENEW']]];
            } elseif (!$bAnnounce) {
                $new_criteria += ['$and' => [['tags.cod' => ['$nin' => ['PUDENEW']]]]];
            }
        } elseif (('' !== $bChannel) && $bChannel) {
            $new_criteria += ['$and' => [['tags.cod' => $sChannelValue]]];
        }

        if ($bStatus) {
            if (!empty($aStatus)) {
                $aStatus = array_map('intval', $aStatus);
                $new_criteria['status'] += ['$in' => $aStatus];
            }
        }

        if ($bPerson && $bRole && $personName && $roleCode) {
            $isMongoId = true;

            try {
                new \MongoId($personName);
            } catch (\Exception $exception) {
                $isMongoId = false;
            }
            // Only in Mongo 1.5.0
            // NOTE: $isMongoId = \MongoId::isValid($personName);
            if ($isMongoId) {
                $peopleCriteria = new \MongoId($personName);
                $new_criteria['people'] = ['$elemMatch' => ['cod' => $roleCode, 'people._id' => $peopleCriteria]];
            } else {
                $peopleCriteria = ['$regex' => $personName, '$options' => 'i'];
                $new_criteria['people'] = ['$elemMatch' => ['cod' => $roleCode, 'people.name' => $peopleCriteria]];
            }
        } elseif ($bPerson && !$bRole && $personName) {
            $isMongoId = true;

            try {
                new \MongoId($personName);
            } catch (\Exception $exception) {
                $isMongoId = false;
            }
            // Only in Mongo 1.5.0
            // NOTE: $isMongoId = \MongoId::isValid($personName);
            if ($isMongoId) {
                $peopleCriteria = new \MongoId($personName);
                $new_criteria += ['people.people._id' => $peopleCriteria];
            } else {
                $peopleCriteria = ['$regex' => $personName, '$options' => 'i'];
                $new_criteria += ['people.people.name' => $peopleCriteria];
            }
        } elseif (!$bPerson && $bRole && $roleCode) {
            $new_criteria['people.cod'] = $roleCode;
        }

        return $new_criteria;
    }

    /**
     * @param $text
     * @param $queryBuilder
     * @param $locale
     */
    public function completeSearchQueryBuilder($text, $queryBuilder, $locale = 'en')
    {
        $text = trim($text);
        if ((false !== strpos($text, '*')) && (false === strpos($text, ' '))) {
            $text = str_replace('*', '.*', $text);
            $text = SearchUtils::scapeTildes($text);
            $mRegex = new \MongoRegex("/{$text}/i");
            $queryBuilder->addOr($queryBuilder->expr()->field('title.'.$locale)->equals($mRegex));
            $queryBuilder->addOr($queryBuilder->expr()->field('people.people.name')->equals($mRegex));
        } else {
            $queryBuilder->field('$text')->equals([
                '$search' => TextIndexUtils::cleanTextIndex($text),
                '$language' => TextIndexUtils::getCloseLanguage($locale),
            ]);
        }
    }

    /**
     * @param $value
     *
     * @return array
     */
    private function processDates($value)
    {
        $criteria = [];
        $date_from = null;
        $date_to = null;

        if ('' !== $value['from']) {
            $date_from = new \DateTime($value['from']);
        }
        if ('' !== $value['to']) {
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

    /**
     * @param       $text
     * @param array $base
     * @param       $locale
     *
     * @return array
     */
    private function getSearchCriteria($text, array $base = [], $locale = 'en')
    {
        $text = trim($text);
        if ((false !== strpos($text, '*')) && (false === strpos($text, ' '))) {
            $text = str_replace('*', '.*', $text);
            $text = SearchUtils::scapeTildes($text);
            $mRegex = new \MongoRegex("/{$text}/i");
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
