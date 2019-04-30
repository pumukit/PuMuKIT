<?php

namespace Pumukit\OpencastBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;

class SeriesSyncService
{
    private $dm;
    private $clientService;
    private $logger;

    public function __construct(DocumentManager $dm, ClientService $clientService, LoggerInterface $logger)
    {
        $this->dm = $dm;
        $this->clientService = $clientService;
        $this->logger = $logger;
    }

    public function createSeries($series)
    {
        //TTK-21470: Since having a series in an Opencast object is not required, but it is in PuMuKIT
        // we need THIS series to not be synced to Opencast. Ideally series would be OPTIONAL.
        if ('default' == $series->getProperty('opencast')) {
            return;
        }

        try {
            $output = $this->clientService->createOpencastSeries($series);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), $e->getTrace());

            return;
        }

        $seriesOpencastId = json_decode($output['var'], true)['identifier'];
        $series->setProperty('opencast', $seriesOpencastId);
        $this->dm->persist($series);
        $this->dm->flush();
    }

    public function updateSeries($series)
    {
        try {
            $this->clientService->updateOpencastSeries($series);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), $e->getTrace());
            if (404 !== $e->getCode()) {
                return;
            }
            $this->createSeries($series);
        }
    }

    public function deleteSeries($series)
    {
        try {
            $this->clientService->deleteOpencastSeries($series);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), $e->getTrace());

            return;
        }
    }
}
