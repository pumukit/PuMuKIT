<?php

namespace Pumukit\SchemaBundle\Filter;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\Filter\BsonFilter;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class ChannelFilter extends BsonFilter
{
    public function addFilterCriteria(ClassMetadata $targetDocument)
    {
        if (MultimediaObject::class === $targetDocument->reflClass->name) {
            return array('tags.cod' => $this->getParameter('channel_tag'));
        }
    }
}
