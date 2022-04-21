<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;

class AutoNumericValueService
{
    protected $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    public function numericalIDForMultimediaObject(MultimediaObject $multimediaObject): void
    {
        $SEMKey = 55555;
        $seg = sem_get($SEMKey, 1, 0666, -1);
        sem_acquire($seg);

        $enableFilters = $this->disableFilters();

        $this->setNumericalIDOnMultimediaObject($multimediaObject);

        $this->enableFilters($enableFilters);
        sem_release($seg);
    }

    public function numericalIDForSeries(Series $series): void
    {
        $SEMKey = 66666;
        $seg = sem_get($SEMKey, 1, 0666, -1);
        sem_acquire($seg);

        $enableFilters = $this->disableFilters();

        $this->setNumericalIDOnSeries($series);

        $this->enableFilters($enableFilters);
        sem_release($seg);
    }

    private function disableFilters(): array
    {
        $enableFilters = array_keys($this->documentManager->getFilterCollection()->getEnabledFilters());
        foreach ($enableFilters as $enableFilter) {
            $this->documentManager->getFilterCollection()->disable($enableFilter);
        }

        return $enableFilters;
    }

    private function enableFilters(array $disabledFilters): void
    {
        foreach ($disabledFilters as $disabledFilter) {
            $this->documentManager->getFilterCollection()->enable($disabledFilter);
        }
    }

    private function setNumericalIDonMultimediaObject(MultimediaObject $multimediaObject)
    {
        $lastMultimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->createQueryBuilder()
            ->field('numerical_id')->exists(true)
            ->sort(['numerical_id' => -1])
            ->getQuery()
            ->getSingleResult()
        ;

        $lastNumericalID = 0;
        if ($lastMultimediaObject instanceof MultimediaObject) {
            $lastNumericalID = $lastMultimediaObject->getNumericalID();
        }

        $newNumericalID = $lastNumericalID + 1;

        $multimediaObject->setNumericalID($newNumericalID);
        $this->documentManager->flush();
    }

    private function setNumericalIDOnSeries(Series $series)
    {
        $lastSeries = $this->documentManager->getRepository(Series::class)->createQueryBuilder()
            ->field('numerical_id')->exists(true)
            ->sort(['numerical_id' => -1])
            ->getQuery()
            ->getSingleResult()
        ;

        $lastNumericalID = 0;
        if ($lastSeries instanceof Series) {
            $lastNumericalID = $lastSeries->getNumericalID();
        }

        $newNumericalID = $lastNumericalID + 1;

        $series->setNumericalID($newNumericalID);
        $this->documentManager->flush();
    }
}
