<?php

namespace Pumukit\SchemaBundle\Filter;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\Filter\BsonFilter;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class SchemaFilter extends BsonFilter
{
    public function addFilterCriteria(ClassMetadata $targetDocument)
    {
        if (MultimediaObject::class === $targetDocument->reflClass->name) {
            return $this->getCriteria();
        }
    }

    protected function getCriteria()
    {
        $criteria = array();

        if ($this->hasParameter('pub_channel_tag')) {
            $criteria['tags.cod'] = $this->getParameter('pub_channel_tag');
        }
        if ($this->hasParameter('status')) {
            $criteria['status'] = $this->getParameter('status');
        }
        if ($this->hasParameter('display_track_tag')) {
            $criteria['$or'] = array(
                array('tracks' => array('$elemMatch' => array('tags' => $this->getParameter('display_track_tag'), 'hide' => false)), 'properties.opencast' => array('$exists' => false)),
                array('properties.opencast' => array('$exists' => true)),
                array('properties.externalplayer' => array('$exists' => true, '$ne' => '')),
            );
        }

        return $criteria;
    }

    private function hasParameter($name)
    {
        if (isset($this->parameters[$name])) {
            return true;
        }

        return false;
    }
}
