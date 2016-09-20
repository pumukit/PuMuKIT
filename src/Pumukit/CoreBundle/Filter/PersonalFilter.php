<?php

namespace Pumukit\CoreBundle\Filter;

use Doctrine\ODM\MongoDB\Mapping\ClassMetaData;
use Doctrine\ODM\MongoDB\Query\Filter\BsonFilter;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class PersonalFilter extends SchemaFilter
{
    public function addFilterCriteria(ClassMetadata $targetDocument)
    {
        if ("Pumukit\SchemaBundle\Document\MultimediaObject" === $targetDocument->reflClass->name) {
            return $this->getMultimediaObjectCriteria();
        }
        if ("Pumukit\SchemaBundle\Document\Series" === $targetDocument->reflClass->name) {
            return $this->getSeriesCriteria();
        }
    }

    private function getMultimediaObjectCriteria()
    {
        $criteria = array();
        $criteria_portal = $this->getCriteria();
        $criteria_backoffice = array();
        if (isset($this->parameters['people']) && isset($this->parameters['groups'])) {
            $criteria_backoffice['$or'] = array(
                array('people' => $this->parameters['people']),
                array('groups' => $this->parameters['groups'])
            );
        }
        if($criteria_portal && $criteria_backoffice)
            $criteria['$or'] = array($criteria_portal, $criteria_backoffice);
        else
            $criteria = $criteria_portal?:$criteria_backoffice;
        return $criteria;
    }

    private function getSeriesCriteria()
    {
        $criteria = array();
        if (isset($this->parameters['person_id']) && isset($this->parameters['role_code']) && isset($this->parameters['series_groups'])) {
            $criteria["_id"] = $this->getSeriesMongoQuery($this->parameters['person_id'], $this->parameters['role_code'], $this->parameters['series_groups']);
        }
        return $criteria;
    }
}
