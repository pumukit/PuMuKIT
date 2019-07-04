<?php

namespace Pumukit\CoreBundle\Filter;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\Filter\BsonFilter;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;

/**
 * Class WebTVFilter.
 */
class WebTVFilter extends BsonFilter
{
    /**
     * @param ClassMetadata $targetDocument
     *
     * @return array|void
     */
    public function addFilterCriteria(ClassMetadata $targetDocument)
    {
        if (MultimediaObject::class === $targetDocument->reflClass->name) {
            return $this->getMultimediaObjectCriteria();
        }
        if (Series::class === $targetDocument->reflClass->name) {
            return $this->getSeriesCriteria();
        }
    }

    /**
     * @return array
     */
    protected function getMultimediaObjectCriteria()
    {
        $criteria = [];
        if ($this->hasParameter('pub_channel_tag')) {
            $criteria['tags.cod'] = $this->getParameter('pub_channel_tag');
        }
        if ($this->hasParameter('status')) {
            $criteria['status'] = $this->getParameter('status');
        }
        if ($this->hasParameter('display_track_tag')) {
            $criteria['$or'] = [
                [
                    'tracks' => [
                        '$elemMatch' => [
                            'tags' => $this->getParameter('display_track_tag'),
                            'hide' => false,
                        ],
                    ],
                    'properties.opencast' => [
                        '$exists' => false,
                    ],
                ],
                [
                    'properties.opencast' => [
                        '$exists' => true,
                    ],
                ],
                [
                    'properties.externalplayer' => [
                        '$exists' => true,
                        '$ne' => '',
                    ],
                ],
            ];
        }

        return $criteria;
    }

    /**
     * @return array
     */
    protected function getSeriesCriteria()
    {
        return [
            'hide' => false,
        ];
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    private function hasParameter($name)
    {
        return isset($this->parameters[$name]);
    }
}
