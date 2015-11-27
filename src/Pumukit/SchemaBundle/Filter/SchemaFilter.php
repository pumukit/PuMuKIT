<?php

namespace Pumukit\SchemaBundle\Filter;

use Doctrine\ODM\MongoDB\Mapping\ClassMetaData;
use Doctrine\ODM\MongoDB\Query\Filter\BsonFilter;
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
                         'tags.cod' => $this->getParameter('pub_channel_tag'),
                          );
        if ($this->hasParameter('status')) {
            $criteria['status'] = $this->getParameter('status');
        }
        if ($this->hasParameter('private_broadcast')) {
            $privateBroadcastCriteria = $this->getParameter('private_broadcast');
            if (null != $privateBroadcastCriteria) {
                $criteria['broadcast'] = $privateBroadcastCriteria;
            }
        }
        if ($this->hasParameter('display_track_tag')) {
            $criteria['$or'] = array(
                                     array('tracks' => array('$elemMatch' => array('tags' => $this->getParameter('display_track_tag'), 'hide' => false)), 'properties.opencast' => array('$exists' => false)),
                                     array('properties.opencast' => array('$exists' => true)),
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
