<?php

namespace Pumukit\SchemaBundle\Filter;

use Doctrine\ODM\MongoDB\Mapping\ClassMetaData;
use Doctrine\ODM\MongoDB\Query\Filter\BsonFilter;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Broadcast;

class AdminFilter extends BsonFilter
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
        if (isset($this->parameters['people'])) {
            $criteria['people'] = $this->parameters['people'];
        }

        return $criteria;
    }

    private function getSeriesCriteria()
    {
        $criteria = array();
        if (isset($this->parameters['series_ids'])) {
            $criteria["_id"] = $this->parameters['series_ids'];
        }

        return $criteria;
    }
}