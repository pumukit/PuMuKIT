<?php

namespace Pumukit\CoreBundle\Filter;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;

class PersonalFilter extends WebTVFilter
{
    public function addFilterCriteria(ClassMetadata $targetDocument)
    {
        if (MultimediaObject::class === $targetDocument->reflClass->name) {
            return $this->getMultimediaObjectCriteria();
        }
        if (Series::class === $targetDocument->reflClass->name) {
            return $this->getSeriesCriteria();
        }
    }

    protected function getMultimediaObjectCriteria()
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

    protected function getSeriesCriteria()
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
     * Get series mongo query
     * Match the Series
     * with given ids.
     * Query in MongoDB:
     * db.Series.find({ "_id": { "$in": [ ObjectId("__id_1__"), ObjectId("__id_2__")... ] } });.
     *
     * @param null|string $personId
     * @param null|string $roleCode
     * @param array       $groups
     *
     * @return array
     */
    private function getSeriesMongoQuery($personId, $roleCode, $groups)
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
