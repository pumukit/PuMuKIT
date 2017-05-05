<?php

namespace Pumukit\NewAdminBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class MultimediaObjectSearchService
{
    private $dm;

    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
    }

    public function processMMOCriteria($reqCriteria)
    {
        $new_criteria = array('status' => array('$ne' => MultimediaObject::STATUS_PROTOTYPE));
        $bAnnounce = '';
        $bChannel = '';
        $bPerson = false;
        $bRole = false;
        $bStatus = false;
        $personName = '';
        $roleCode = '';

        foreach ($reqCriteria as $property => $value) {
            if (('search' === $property) && ('' !== $value)) {
                $new_criteria['$or'] = array(
                    array('_id' => array('$in' => array($value))),
                    array('$text' => array('$search' => $value)),
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

        if ('' !== $bAnnounce) {
            if (('' !== $bChannel) && $bChannel && $bAnnounce) {
                $new_criteria += array('$and' => array(array('tags.cod' => $sChannelValue), array('tags.cod' => 'PUDENEW')));
            } elseif (('' !== $bChannel) && $bChannel) {
                $new_criteria += array('$and' => array(array('tags.cod' => $sChannelValue)));
            } elseif ($bAnnounce) {
                $new_criteria += array('$and' => array(array('tags.cod' => 'PUDENEW')));
            } elseif (!$bAnnounce) {
                $new_criteria += array('$and' => array(array('tags.cod' => array('$nin' => array('PUDENEW')))));
            }
        } elseif (('' !== $bChannel) && $bChannel) {
            $new_criteria += array('$and' => array(array('tags.cod' => $sChannelValue)));
        }

        if ($bStatus) {
            if (!empty($aStatus)) {
                $aStatus = array_map('intval', $aStatus);
                $new_criteria['status'] += array('$in' => $aStatus);
            }
        }

        if ($bPerson && $bRole && $personName && $roleCode) {
            $isMongoId = \MongoId::isValid($personName);
            if ($isMongoId) {
                $peopleCriteria = new \MongoId($personName);
                $new_criteria['people'] = array('$elemMatch' => array('cod' => $roleCode, 'people._id' => $peopleCriteria));
            } else {
                $peopleCriteria = array('$regex' => $personName, '$options' => 'i');
                $new_criteria['people'] = array('$elemMatch' => array('cod' => $roleCode, 'people.name' => $peopleCriteria));
            }
        } elseif ($bPerson && !$bRole && $personName) {
            $isMongoId = \MongoId::isValid($personName);
            if ($isMongoId) {
                $peopleCriteria = new \MongoId($personName);
                $new_criteria += array('people.people._id' => $peopleCriteria);
            } else {
                $peopleCriteria = array('$regex' => $personName, '$options' => 'i');
                $new_criteria += array('people.people.name' => $peopleCriteria);
            }
        } elseif (!$bPerson && $bRole && $roleCode) {
            $new_criteria['people.cod'] = $roleCode;
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
}
