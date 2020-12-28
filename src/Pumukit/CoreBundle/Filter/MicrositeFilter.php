<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Filter;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\Filter\BsonFilter;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class MicrositeFilter extends BsonFilter
{
    public function addFilterCriteria(ClassMetadata $targetDocument): array
    {
        if (MultimediaObject::class === $targetDocument->reflClass->name) {
            return ['tags.cod' => $this->getParameter('microsite_tag')];
        }

        return [];
    }
}
