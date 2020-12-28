<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Filter;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;

class PersonalFilter extends WebTVFilter
{
    protected function getMultimediaObjectCriteria(): array
    {
        $criteria = [];
        $criteria_portal = parent::getMultimediaObjectCriteria();
        $criteria_backoffice = [];
        if (isset($this->parameters['people'], $this->parameters['groups'])) {
            $criteria_backoffice['$or'] = [
                ['people' => $this->parameters['people']],
                ['groups' => $this->parameters['groups']],
            ];
        }
        if ($criteria_portal && $criteria_backoffice) {
            $criteria['$or'] = [$criteria_portal, $criteria_backoffice];
        } else {
            $criteria = $criteria_portal ?: $criteria_backoffice;
        }

        return $criteria;
    }

    protected function getSeriesCriteria(): array
    {
        $criteria = [];
        if (isset($this->parameters['person_id'], $this->parameters['role_code'], $this->parameters['series_groups'])) {
            $criteria['_id'] = $this->getSeriesMongoQuery(
                $this->parameters['person_id'],
                $this->parameters['role_code'],
                $this->parameters['series_groups']
            );
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
            $referencedSeries = $repoMmobj->findSeriesFieldByPersonIdAndRoleCodOrGroups(
                $personId,
                $roleCode,
                $groups
            );
            $seriesIds['$in'] = $referencedSeries->toArray();
        }

        return $seriesIds;
    }
}
