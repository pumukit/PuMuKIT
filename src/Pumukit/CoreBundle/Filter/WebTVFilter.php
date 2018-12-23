<?php

namespace Pumukit\CoreBundle\Filter;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\Filter\BsonFilter;

class WebTVFilter extends BsonFilter
{
    public function addFilterCriteria(ClassMetadata $targetDocument)
    {
        if ("Pumukit\SchemaBundle\Document\MultimediaObject" === $targetDocument->reflClass->name) {
            return $this->getCriteria();
        }
        if ("Pumukit\SchemaBundle\Document\Series" === $targetDocument->reflClass->name) {
            return array('hide' => false);
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
        return isset($this->parameters[$name]);
    }
}
