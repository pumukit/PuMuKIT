<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Filter;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\Filter\BsonFilter;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;

class WebTVFilter extends BsonFilter
{
    public function addFilterCriteria(ClassMetadata $targetDocument): array
    {
        if (MultimediaObject::class === $targetDocument->reflClass->name) {
            return $this->getMultimediaObjectCriteria();
        }
        if (Series::class === $targetDocument->reflClass->name) {
            return $this->getSeriesCriteria();
        }

        return [];
    }

    protected function getMultimediaObjectCriteria(): array
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

    protected function getSeriesCriteria(): array
    {
        return [
            'hide' => false,
        ];
    }

    private function hasParameter(string $name): bool
    {
        return isset($this->parameters[$name]);
    }
}
