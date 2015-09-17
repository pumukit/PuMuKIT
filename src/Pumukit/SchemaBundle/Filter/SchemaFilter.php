<?php

namespace Pumukit\SchemaBundle\Filter;

use Doctrine\ODM\MongoDB\Mapping\ClassMetaData;
use Doctrine\ODM\MongoDB\Query\Filter\BsonFilter;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Broadcast;

class SchemaFilter extends BsonFilter
{
    public function addFilterCriteria(ClassMetadata $targetDocument)
    {
        if ("Pumukit\SchemaBundle\Document\MultimediaObject" === $targetDocument->reflClass->name) {
          return $this->getCriteria();
        }
    }

    private function getCriteria()
    {
        $criteria = array(
                         "status" => MultimediaObject::STATUS_PUBLISHED,
                         "tags.cod" => $this->getParameter("pub_channel_tag")
                          );
        $privateBroadcastCriteria = $this->getParameter("private_broadcast");
        if (null != $privateBroadcastCriteria) {
            $criteria['broadcast'] = $privateBroadcastCriteria;
        }
        return $criteria;
    }
}