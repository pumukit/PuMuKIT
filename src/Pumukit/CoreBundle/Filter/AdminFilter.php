<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Filter;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\Filter\BsonFilter;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;

class AdminFilter extends BsonFilter
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

    private function getMultimediaObjectCriteria(): array
    {
        $criteria = [];
        if (isset($this->parameters['people'], $this->parameters['groups'])) {
            $criteria['$or'] = [
                ['people' => $this->parameters['people']],
                ['groups' => $this->parameters['groups']],
            ];
        }

        return $criteria;
    }

    private function getSeriesCriteria(): array
    {
        $criteria = [];
        if (isset($this->parameters['person_id'], $this->parameters['role_code'], $this->parameters['series_groups'])) {
            $criteria['_id'] = $this->getSeriesMongoQuery($this->parameters['person_id'], $this->parameters['role_code'], $this->parameters['series_groups']);
        }

        return $criteria;
    }

    /**
     * Get series mongo query Match the Series with given ids.
     * Query in MongoDB: db.Series.find({ "_id": { "$in": [ ObjectId("__id_1__"), ObjectId("__id_2__")... ] } });.
     */
    private function getSeriesMongoQuery(?string $personId, ?string $roleCode, array $groups): array
    {
        $seriesIds = [];
        if ((null !== $personId) && (null !== $roleCode)) {
            $repoMmobj = $this->dm->getRepository(MultimediaObject::class);
            $referencedSeries = $repoMmobj->findSeriesFieldByPersonIdAndRoleCodOrGroups($personId, $roleCode, $groups);
            $seriesIds['$in'] = $referencedSeries->toArray();
        }

        return $seriesIds;
    }
}
