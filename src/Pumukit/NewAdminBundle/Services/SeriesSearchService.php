<?php

namespace Pumukit\NewAdminBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;

class SeriesSearchService
{
    private $dm;

    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
    }

    public function processCriteria($reqCriteria, $searchInObjects = false)
    {
        $new_criteria = array();

        foreach ($reqCriteria as $property => $value) {
            if (('search' === $property) && ('' !== $value)) {
                if ($searchInObjects) {
                    $mmRepo = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
                    $ids = $mmRepo->getIdsWithSeriesTextOrId($value, 100)->toArray();
                    $ids[] = $value;

                    $new_criteria['$or'] = array(
                      array('_id' => array('$in' => $ids)),
                      array('$text' => array('$search' => $value)),
                    );
                } else {
                    $new_criteria['$or'] = array(
                      array('_id' => $value),
                      array('$text' => array('$search' => $value)),
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
            }
        }

        return $new_criteria;
    }

    private function processDates($value)
    {
        $criteria = array();

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

    public function processMMOCriteria($reqCriteria)
    {
        $new_criteria = array();

        foreach ($reqCriteria as $property => $value) {
            if (('search' === $property) && ('' !== $value)) {

                $new_criteria['$or'] = array(
                    array('_id' => array('$in' => array($value))),
                    array('$text' => array('$search' => $value)),
                );

            } elseif (('date' == $property) && ('' !== $value)) {
                $new_criteria += $this->processDates($value);
            } elseif (('announce' === $property) && ('' !== $value)) {
                if ('true' === $value) {
                    $new_criteria[$property] = true;
                } elseif ('false' === $value) {
                    $new_criteria[$property] = false;
                }
            } elseif (('person_name' === $property) && ('' !== $value)) {
                $new_criteria['people.people'] = array(
                    array('_id' => array('$in' => array($value))),
                    array('name' => array('$search' => $value)),
                );
            } elseif (('person_role' === $property) && ('' !== $value)) {
                if('all' !== $value) {
                    $new_criteria['people.cod'] = array(array('people.cod' => $value),);
                }
            } elseif (('channel' === $property) && ('' !== $value)) {
                if('all' !== $value) {
                    $new_criteria['tag.cod'] = array(
                        array('tag.cod' => $value),
                    );
                }
            }
        }

        return $new_criteria;
    }
}
