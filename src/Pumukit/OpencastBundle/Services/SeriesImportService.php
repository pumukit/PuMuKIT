<?php

namespace Pumukit\OpencastBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Document\Series;

class SeriesImportService
{
    public function __construct(DocumentManager $documentManager, FactoryService $factoryService, array $otherLocales = array())
    {
        $this->dm = $documentManager;
        $this->factoryService = $factoryService;
        $this->otherLocales = $otherLocales;
    }

    public function importSeries($mediaPackage, User $loggedInUser = null)
    {
        $seriesRepo = $this->dm->getRepository('PumukitSchemaBundle:Series');
        $seriesOpencastId = $this->getMediaPackageField($mediaPackage, 'series');
        if (isset($seriesOpencastId)) {
            $series = $seriesRepo->findOneBy(array('properties.opencast' => $seriesOpencastId));
            if (!isset($series)) {
                $seriesTitle = $this->getMediaPackageField($mediaPackage, 'seriestitle');
                $series = $this->createSeries($seriesTitle, $seriesOpencastId, $loggedInUser);
            }
        } elseif ($this->getMediaPackageField($mediaPackage, 'spatial')) {
            $seriesOpencastSpatial = $this->getMediaPackageField($mediaPackage, 'spatial');
            $series = $seriesRepo->findOneBy(array('properties.opencastspatial' => $seriesOpencastSpatial));
            if (!isset($series)) {
                $seriesTitle = $this->getMediaPackageField($mediaPackage, 'seriestitle');
                $series = $this->createSeries($seriesOpencastSpatial, $seriesOpencastSpatial, $loggedInUser, true);
            }
        } else {
            $series = $seriesRepo->findOneBy(array('properties.opencast' => 'default'));
            if (!isset($series)) {
                $series = $this->createSeries('MediaPackages without series', 'default', $loggedInUser);
            }
        }

        return $series;
    }

    private function createSeries($title, $properties, User $loggedInUser = null, $spatial = false)
    {
        $publicDate = new \DateTime('now');

        $series = $this->factoryService->createSeries($loggedInUser);
        $series->setPublicDate($publicDate);
        $series->setTitle($title);
        foreach ($this->otherLocales as $locale) {
            $series->setTitle($title, $locale);
        }

        $series->setProperty($spatial ? 'opencastspatial' : 'opencast', $properties);

        $this->dm->persist($series);
        $this->dm->flush();

        return $series;
    }

    private function getMediaPackageField($mediaFields = array(), $field = '')
    {
        if ($mediaFields && $field) {
            if (isset($mediaFields[$field])) {
                return $mediaFields[$field];
            }
        }

        return null;
    }
}
